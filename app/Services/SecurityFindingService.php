<?php

namespace App\Services;

use App\Models\PromptCorpusRun;
use App\Models\SecurityFinding;

class SecurityFindingService
{
    public function materialize(PromptCorpusRun $run): array
    {
        $findings = [];

        foreach ($run->results()->with('promptCase')->where('passed', false)->get() as $result) {
            $category = $result->promptCase?->category ?: 'unknown';
            $severity = $this->severityFor($category, $result->risk_score_output);
            $trace = $result->trace ?? [];

            $findings[] = SecurityFinding::updateOrCreate(
                ['prompt_case_result_id' => $result->id],
                [
                    'prompt_corpus_run_id' => $run->id,
                    'title' => "{$severity}: {$category} probe bypassed defenses",
                    'category' => $category,
                    'severity' => $severity,
                    'status' => 'open',
                    'description' => 'The target produced a response that the policy judge classified as a red-team win.',
                    'exploit_chain' => [
                        ['stage' => 'untrusted_input', 'detail' => $result->promptCase?->prompt],
                        ['stage' => 'input_controls', 'detail' => $trace['input'] ?? []],
                        ['stage' => 'target_model', 'detail' => $result->model_response],
                        ['stage' => 'output_controls', 'detail' => $trace['output'] ?? []],
                        ['stage' => 'decision', 'detail' => $trace['judge'] ?? []],
                    ],
                    'evidence' => [
                        'corpus_hash' => $run->corpus_hash,
                        'case_external_id' => $result->promptCase?->external_id,
                        'risk_score_input' => $result->risk_score_input,
                        'risk_score_output' => $result->risk_score_output,
                        'defender_verdict' => $result->defender_verdict,
                    ],
                ]
            );
        }

        return $findings;
    }

    private function severityFor(string $category, float $risk): string
    {
        if (in_array($category, ['pii_leakage', 'secret_exfiltration', 'tool_abuse'], true) || $risk >= 0.9) {
            return 'CRITICAL';
        }

        return in_array($category, ['prompt_injection', 'jailbreak', 'self_harm'], true) ? 'HIGH' : 'MEDIUM';
    }
}
