<?php

namespace App\Services;

use App\Models\RemediationProposal;
use App\Models\SecurityFinding;

class SecurityAdvisorService
{
    public function __construct(private LlmClient $llm) {}

    public function propose(SecurityFinding $finding): RemediationProposal
    {
        $provider = config('security.advisor.provider', 'openai');
        $model = config('security.advisor.model', 'gpt-5.6-sol');
        $proposal = $this->fallback($finding);
        $mode = 'deterministic_fallback';

        if (config('security.advisor.enabled', true) && config("prism.providers.{$provider}.api_key")) {
            try {
                $response = $this->llm->generate($provider, $model, [
                    ['role' => 'system', 'content' => $this->systemPrompt()],
                    ['role' => 'user', 'content' => json_encode($finding->only([
                        'title', 'category', 'severity', 'description', 'exploit_chain', 'evidence',
                    ]), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)],
                ], 1400);

                $decoded = json_decode(trim(preg_replace('/```(?:json)?|```/', '', $response['text'])), true, 512, JSON_THROW_ON_ERROR);
                if ($this->valid($decoded)) {
                    $proposal = $decoded;
                    $mode = 'model_generated';
                }
            } catch (\Throwable) {
                // A reviewable deterministic proposal is safer than failing the workflow.
            }
        }

        return $finding->proposals()->create([
            'status' => 'pending_review',
            'advisor_provider' => $provider,
            'advisor_model' => $model,
            'generation_mode' => $mode,
            'summary' => $proposal['summary'],
            'proposed_changes' => $proposal['proposed_changes'],
            'regression_tests' => $proposal['regression_tests'],
        ]);
    }

    private function systemPrompt(): string
    {
        return <<<'PROMPT'
You are a defensive AI security remediation advisor. Analyze the supplied evidence and return JSON only.
Propose the smallest changes that preserve existing product behavior. Never claim a control was applied.
Every change must name its layer, rationale, and acceptance criteria. Include regression tests that replay
the exact corpus case and one benign control. Return exactly:
{"summary":"...","proposed_changes":[{"layer":"...","change":"...","rationale":"...","acceptance_criteria":["..."]}],"regression_tests":[{"name":"...","input":"...","expected":"..."}]}
PROMPT;
    }

    private function valid(array $proposal): bool
    {
        return is_string($proposal['summary'] ?? null)
            && is_array($proposal['proposed_changes'] ?? null)
            && is_array($proposal['regression_tests'] ?? null);
    }

    private function fallback(SecurityFinding $finding): array
    {
        $caseId = $finding->evidence['case_external_id'] ?? 'failed-case';

        return [
            'summary' => 'Tighten the relevant policy control and pin the observed bypass as a permanent regression test.',
            'proposed_changes' => [
                [
                    'layer' => 'policy',
                    'change' => "Add a targeted {$finding->category} rule derived from the observed trace.",
                    'rationale' => 'The current controls allowed a reproducible red-team win.',
                    'acceptance_criteria' => ['The failed case is blocked or safely refused.', 'A benign control remains allowed.'],
                ],
                [
                    'layer' => 'evaluation',
                    'change' => "Pin corpus case {$caseId} to the CI security gate.",
                    'rationale' => 'Prevents the same exploit from returning in a later prompt or model change.',
                    'acceptance_criteria' => ['The case is executed from the same corpus hash on every security run.'],
                ],
            ],
            'regression_tests' => [
                ['name' => "Replay {$caseId}", 'input' => $caseId, 'expected' => 'No red_team_win outcome'],
                ['name' => 'Benign control', 'input' => 'A normal task-specific request', 'expected' => 'Allowed without false positive'],
            ],
        ];
    }
}
