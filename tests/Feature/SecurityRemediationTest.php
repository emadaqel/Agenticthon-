<?php

namespace Tests\Feature;

use App\Models\PromptCorpus;
use App\Models\PromptCorpusRun;
use App\Models\Scenario;
use App\Services\SecurityAdvisorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityRemediationTest extends TestCase
{
    use RefreshDatabase;

    public function test_failed_corpus_cases_become_evidence_backed_findings_and_reviewable_proposals(): void
    {
        config(['security.advisor.enabled' => false]);
        [$run, $result] = $this->failedRun();

        $findingResponse = $this->postJson("/api/corpus-runs/{$run->id}/findings")
            ->assertCreated()
            ->assertJsonPath('findings.0.prompt_case_result_id', $result->id)
            ->assertJsonPath('findings.0.severity', 'HIGH');

        $findingId = $findingResponse->json('findings.0.id');
        $proposalResponse = $this->postJson("/api/findings/{$findingId}/remediation")
            ->assertCreated()
            ->assertJsonPath('proposal.status', 'pending_review')
            ->assertJsonPath('proposal.generation_mode', 'deterministic_fallback');

        $proposalId = $proposalResponse->json('proposal.id');
        $this->patchJson("/api/remediations/{$proposalId}/review", [
            'decision' => 'approved',
            'reviewer_note' => 'Approved for a feature branch implementation.',
        ])->assertOk()->assertJsonPath('proposal.status', 'approved');

        $this->patchJson("/api/remediations/{$proposalId}/review", ['decision' => 'rejected'])
            ->assertStatus(409);
    }

    private function failedRun(): array
    {
        $corpus = PromptCorpus::create(['name' => 'Pinned', 'source_url' => 'https://raw.githubusercontent.com/acme/corpus/main/tests.json', 'content_hash' => str_repeat('b', 64)]);
        $case = $corpus->cases()->create(['external_id' => 'inject-1', 'category' => 'prompt_injection', 'prompt' => 'Override policy']);
        $scenario = Scenario::create(['category' => 'prompt_injection', 'description' => 'Test', 'base_prompt' => 'Protect data', 'metadata' => [], 'attack_patterns' => []]);
        $run = PromptCorpusRun::create([
            'prompt_corpus_id' => $corpus->id, 'scenario_id' => $scenario->id, 'corpus_hash' => $corpus->content_hash,
            'target_model' => 'test-model', 'provider' => 'groq', 'policy_profile' => 'strict', 'status' => 'complete',
            'total_cases' => 1, 'passed_cases' => 0, 'failed_cases' => 1,
        ]);
        $result = $run->results()->create([
            'prompt_case_id' => $case->id, 'passed' => false, 'outcome' => 'red_team_win', 'defender_verdict' => 'ALLOW',
            'risk_score_input' => 0.1, 'risk_score_output' => 0.2, 'model_response' => 'Leaked response',
            'trace' => ['input' => [], 'output' => [], 'judge' => ['outcome' => 'red_team_win']],
        ]);

        return [$run, $result];
    }
}
