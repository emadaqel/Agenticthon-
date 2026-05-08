<?php

namespace App\Jobs;

use App\Models\Scenario;
use App\Models\DuelTurn;
use App\Models\DuelSummary;
use App\AI\Agents\AttackerAgent;
use App\AI\Agents\DefenderAgent;
use App\AI\Agents\PolicyJudgeAgent;
use App\Services\ModelGateway;
use App\Services\NeMoGuardrailsService;
use App\Services\LlmGuardService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class RunDuelJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300; // 5 minutes max per duel

    public function __construct(
        public string $duelId,
        public string $scenarioId,
        public int    $maxTurns,
        public string $policyProfile,
        public string $targetModel,
        public string $provider,
    ) {}

    public function handle(
        AttackerAgent         $attacker,
        DefenderAgent         $defender,
        PolicyJudgeAgent      $judge,
        ModelGateway          $gateway,
        NeMoGuardrailsService $nemo,
        LlmGuardService       $llmGuard,
    ): void {
        $scenario = Scenario::findOrFail($this->scenarioId);
        $history  = [];
        $turns    = [];

        Cache::put("duel:{$this->duelId}:status", [
            'status'  => 'running',
            'turns'   => [],
            'summary' => null,
        ], 600);

        for ($i = 1; $i <= $this->maxTurns; $i++) {
            Cache::put("duel:{$this->duelId}:current_turn", $i, 600);

            // 1 ── Attacker generates adversarial prompt
            $attack = $attacker->generate($scenario, $history);
            $adversarialPrompt = $attack['prompt'] ?? '';

            // 2 ── Guardrail: check input
            $nemoInputResult  = $nemo->checkInput($adversarialPrompt);
            $guardInputResult = $llmGuard->scanInput($adversarialPrompt);

            // If input blocked by NeMo → blue win, skip to next turn
            if ($nemoInputResult['blocked'] ?? false) {
                $turnRecord = $this->buildTurnRecord($scenario->id, $i, $attack, $guardInputResult, $nemoInputResult, '—BLOCKED BEFORE MODEL—', ['risk_score' => 1.0], ['blocked' => true], 'BLOCK', 0);
                $turnRecord['judge_outcome']     = 'blue_team_win';
                $turnRecord['owasp_category']    = 'LLM01';
                $turnRecord['judge_reasoning']   = 'Blocked at input by NeMo Guardrails.';
                $turnRecord['defender_reasoning'] = 'Input blocked by guardrail before reaching model.';
                $turnRecord['modified_response'] = null;
                $turnRecord['tokens_used']       = 0;

                $this->persistTurn($turnRecord);
                $turns[]   = $turnRecord;
                $history[] = $turnRecord;
                $this->pushLiveStatus($turns);
                continue;
            }

            // 3 ── Call target model
            $modelResult = $gateway->call($adversarialPrompt, $scenario->base_prompt, $this->targetModel, $this->provider);

            // 4 ── Guardrail: check output
            $nemoOutputResult  = $nemo->checkOutput($modelResult['response']);
            $guardOutputResult = $llmGuard->scanOutput($modelResult['response']);

            // 5 ── Defender evaluates
            $defenseResult = $defender->evaluate(
                $adversarialPrompt,
                $guardInputResult,
                $modelResult['response'],
                $guardOutputResult,
                $this->policyProfile
            );

            // 6 ── PolicyJudge scores the turn
            $judgeInput  = array_merge($attack, [
                'model_response'   => $modelResult['response'],
                'defender_verdict' => $defenseResult['verdict'] ?? 'BLOCK',
            ]);
            $judgeResult = $judge->scoreTurn($judgeInput);

            // 7 ── Build full turn record & persist
            $turnRecord = $this->buildTurnRecord(
                $scenario->id, $i, $attack,
                $guardInputResult, $nemoInputResult,
                $modelResult['response'],
                $guardOutputResult, $nemoOutputResult,
                $defenseResult['verdict'] ?? 'BLOCK',
                $modelResult['latency_ms'] ?? 0,
            );

            $turnRecord['judge_outcome']      = $judgeResult['outcome']        ?? 'draw';
            $turnRecord['owasp_category']     = $judgeResult['owasp_category'] ?? 'LLM01';
            $turnRecord['judge_reasoning']    = $judgeResult['reasoning']      ?? '';
            $turnRecord['defender_reasoning'] = $defenseResult['reasoning']    ?? '';
            $turnRecord['modified_response']  = $defenseResult['modified_response'] ?? null;
            $turnRecord['tokens_used']        = ($modelResult['tokens_input'] ?? 0) + ($modelResult['tokens_output'] ?? 0);

            $this->persistTurn($turnRecord);
            $turns[]   = $turnRecord;
            $history[] = $turnRecord;
            $this->pushLiveStatus($turns);

            // If attacker got through → red win
            if ($turnRecord['judge_outcome'] === 'red_team_win') {
                break;
            }
        }

        // 8 ── Final duel summary
        $summary = $judge->summarize($this->duelId, $scenario->category, $turns);

        DuelSummary::create([
            'duel_id'               => $this->duelId,
            'scenario_id'           => $scenario->id,
            'target_model'          => $this->targetModel,
            'policy_profile'        => $this->policyProfile,
            'total_turns'           => $summary['total_turns'],
            'red_team_wins'         => $summary['red_team_wins'],
            'blue_team_wins'        => $summary['blue_team_wins'],
            'draws'                 => $summary['draws'],
            'false_positives'       => $summary['false_positives'],
            'attack_success_rate'   => $summary['attack_success_rate'],
            'defense_effectiveness' => $summary['defense_effectiveness'],
            'owasp_categories'      => $summary['owasp_categories_triggered'],
        ]);

        Cache::put("duel:{$this->duelId}:status", [
            'status'  => 'complete',
            'turns'   => $turns,
            'summary' => $summary,
        ], 600);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function buildTurnRecord(
        string $scenarioId, int $turn, array $attack,
        array $guardInputResult, array $nemoInputResult,
        string $modelResponse,
        array $guardOutputResult, array $nemoOutputResult,
        string $verdict, int $latencyMs,
    ): array {
        return [
            'duel_id'                => $this->duelId,
            'scenario_id'            => $scenarioId,
            'turn'                   => $turn,
            'attacker_technique'     => $attack['technique_used'] ?? 'unknown',
            'attacker_reasoning'     => $attack['reasoning']      ?? '',
            'adversarial_prompt'     => $attack['prompt']         ?? '',
            'guardrail_input_result' => array_merge($guardInputResult, $nemoInputResult),
            'model_response'         => $modelResponse,
            'guardrail_output_result'=> array_merge($guardOutputResult, $nemoOutputResult),
            'defender_verdict'       => $verdict,
            'risk_score_input'       => $guardInputResult['risk_score']  ?? 0.0,
            'risk_score_output'      => $guardOutputResult['risk_score'] ?? 0.0,
            'latency_ms'             => $latencyMs,
            'policy_profile'         => $this->policyProfile,
            'source'                 => 'web',
        ];
    }

    private function persistTurn(array $turnRecord): void
    {
        DuelTurn::create([
            'duel_id'                => $turnRecord['duel_id'],
            'scenario_id'            => $turnRecord['scenario_id'],
            'turn'                   => $turnRecord['turn'],
            'attacker_technique'     => $turnRecord['attacker_technique'],
            'adversarial_prompt'     => $turnRecord['adversarial_prompt'],
            'guardrail_input_result' => $turnRecord['guardrail_input_result'],
            'model_response'         => $turnRecord['model_response'],
            'guardrail_output_result'=> $turnRecord['guardrail_output_result'],
            'defender_verdict'       => $turnRecord['defender_verdict'],
            'judge_outcome'          => $turnRecord['judge_outcome'] ?? null,
            'owasp_category'         => $turnRecord['owasp_category'] ?? null,
            'risk_score_input'       => $turnRecord['risk_score_input'],
            'risk_score_output'      => $turnRecord['risk_score_output'],
            'latency_ms'             => $turnRecord['latency_ms'],
            'tokens_used'            => $turnRecord['tokens_used'] ?? 0,
            'source'                 => $turnRecord['source'] ?? 'web',
        ]);
    }

    private function pushLiveStatus(array $turns): void
    {
        $current = Cache::get("duel:{$this->duelId}:status", []);
        Cache::put("duel:{$this->duelId}:status", array_merge($current, ['turns' => $turns]), 600);
    }
}
