<?php

namespace Tests\Feature;

use App\Models\PromptCorpus;
use App\Models\PromptCorpusRun;
use App\Models\Scenario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityEvidenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_proves_attacks_are_removed_without_breaking_benign_behavior(): void
    {
        $this->getJson('/api/security/demo')->assertOk()
            ->assertJsonPath('demo.before.failed', 2)
            ->assertJsonPath('demo.after.failed', 0)
            ->assertJsonPath('demo.improvement.benign_pass_rate_preserved', true);

        $this->artisan('arena:demo-security --json')->assertSuccessful();
    }

    public function test_gate_fails_and_report_marks_unavailable_controls_truthfully(): void
    {
        [$run, $result] = $this->failedRun();

        $this->getJson("/api/corpus-runs/{$run->id}/gate")
            ->assertUnprocessable()
            ->assertJsonPath('gate.status', 'fail')
            ->assertJsonPath('gate.metrics.control_unavailable_cases', 1);

        $this->getJson("/api/corpus-runs/{$run->id}/security-report")
            ->assertOk()
            ->assertJsonPath('report.provenance.corpus_hash', $run->corpus_hash)
            ->assertJsonPath('report.control_evidence.0.controls.nemo_input.status', 'unavailable')
            ->assertJsonPath('report.control_evidence.0.controls.llm_guard_input.status', 'bypassed');
    }

    private function failedRun(): array
    {
        $corpus = PromptCorpus::create(['name' => 'Pinned', 'source_url' => 'https://raw.githubusercontent.com/acme/corpus/main/tests.json', 'content_hash' => str_repeat('c', 64)]);
        $case = $corpus->cases()->create(['external_id' => 'case-1', 'category' => 'prompt_injection', 'prompt' => 'override']);
        $scenario = Scenario::create(['category' => 'prompt_injection', 'description' => 'Test', 'base_prompt' => 'Protect data', 'metadata' => [], 'attack_patterns' => []]);
        $run = PromptCorpusRun::create([
            'prompt_corpus_id' => $corpus->id, 'scenario_id' => $scenario->id, 'corpus_hash' => $corpus->content_hash,
            'target_model' => 'test', 'provider' => 'groq', 'policy_profile' => 'strict', 'status' => 'complete',
            'total_cases' => 1, 'passed_cases' => 0, 'failed_cases' => 1,
        ]);
        $result = $run->results()->create([
            'prompt_case_id' => $case->id, 'passed' => false, 'outcome' => 'red_team_win', 'defender_verdict' => 'ALLOW',
            'risk_score_input' => 0, 'risk_score_output' => 0, 'model_response' => 'unsafe',
            'trace' => [
                'input' => ['nemo' => ['flagged_for_review' => true], 'llm_guard' => ['risk_score' => 0, 'scanners_triggered' => []]],
                'output' => ['nemo' => [], 'llm_guard' => []],
            ],
        ]);

        return [$run, $result];
    }
}
