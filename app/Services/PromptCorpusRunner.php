<?php

namespace App\Services;

use App\AI\Agents\DefenderAgent;
use App\AI\Agents\PolicyJudgeAgent;
use App\Models\PromptCorpus;
use App\Models\PromptCorpusRun;
use App\Models\Scenario;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PromptCorpusRunner
{
    public function __construct(
        private readonly ModelGateway $gateway,
        private readonly NeMoGuardrailsService $nemo,
        private readonly LlmGuardService $llmGuard,
        private readonly DefenderAgent $defender,
        private readonly PolicyJudgeAgent $judge,
    ) {}

    public function run(PromptCorpus $corpus, Scenario $scenario, array $options): PromptCorpusRun
    {
        if (! $corpus->content_hash) {
            throw new RuntimeException('Synchronize the corpus before running it.');
        }

        $model = $options['target_model'] ?? 'llama-3.1-8b-instant';
        $provider = $options['provider'] ?? 'groq';
        $policy = $options['policy_profile'] ?? 'strict';
        $limit = max(1, min((int) ($options['max_cases'] ?? 25), 100));

        $run = PromptCorpusRun::create([
            'prompt_corpus_id' => $corpus->id,
            'scenario_id' => $scenario->id,
            'corpus_hash' => $corpus->content_hash,
            'target_model' => $model,
            'provider' => $provider,
            'policy_profile' => $policy,
            'metadata' => ['source_url' => $corpus->source_url, 'source_ref' => $corpus->source_ref],
        ]);

        $passed = 0;
        $failed = 0;

        foreach ($corpus->cases()->orderBy('external_id')->limit($limit)->get() as $case) {
            $inputNemo = $this->nemo->checkInput($case->prompt, $policy);
            $inputGuard = $this->llmGuard->scanInput($case->prompt, $policy);
            $modelResponse = null;
            $outputNemo = ['blocked' => false];
            $outputGuard = ['risk_score' => 0.0, 'scanners_triggered' => []];
            $defense = ['verdict' => 'BLOCK', 'reasoning' => 'Input blocked before model execution.'];

            if (! ($inputNemo['blocked'] ?? false)) {
                $modelResult = $this->gateway->call($case->prompt, $scenario->base_prompt, $model, $provider);
                $modelResponse = $modelResult['response'] ?? '';
                $outputNemo = $this->nemo->checkOutput($modelResponse, $policy);
                $outputGuard = $this->llmGuard->scanOutput($modelResponse, $policy);
                $defense = $this->defender->evaluate($case->prompt, $inputGuard, $modelResponse, $outputGuard, $policy);
            }

            $judge = $this->judge->scoreTurn([
                'technique_used' => $case->category ?? 'corpus_probe',
                'prompt' => $case->prompt,
                'model_response' => $modelResponse ?? '—BLOCKED BEFORE MODEL—',
                'defender_verdict' => $defense['verdict'] ?? 'BLOCK',
            ]);
            $outcome = $judge['outcome'] ?? 'draw';
            $casePassed = $outcome !== 'red_team_win';
            $casePassed ? $passed++ : $failed++;

            $run->results()->create([
                'prompt_case_id' => $case->id,
                'passed' => $casePassed,
                'outcome' => $outcome,
                'defender_verdict' => $defense['verdict'] ?? null,
                'risk_score_input' => $inputGuard['risk_score'] ?? 0,
                'risk_score_output' => $outputGuard['risk_score'] ?? 0,
                'model_response' => $modelResponse,
                'trace' => [
                    'input' => ['nemo' => $inputNemo, 'llm_guard' => $inputGuard],
                    'output' => ['nemo' => $outputNemo, 'llm_guard' => $outputGuard],
                    'defender' => $defense,
                    'judge' => $judge,
                    'expected_policy' => $case->expected_policy,
                ],
            ]);
        }

        $run->update([
            'status' => 'complete',
            'total_cases' => $passed + $failed,
            'passed_cases' => $passed,
            'failed_cases' => $failed,
        ]);

        return $run->fresh()->load('results.promptCase');
    }
}
