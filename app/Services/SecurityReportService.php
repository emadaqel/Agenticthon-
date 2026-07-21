<?php

namespace App\Services;

use App\Models\PromptCorpusRun;

class SecurityReportService
{
    public function build(PromptCorpusRun $run): array
    {
        $run->loadMissing('corpus', 'scenario', 'results.promptCase', 'findings.proposals');

        return [
            'report_version' => '1.0',
            'generated_at' => now()->toIso8601String(),
            'provenance' => [
                'run_id' => $run->id,
                'corpus' => $run->corpus?->name,
                'corpus_hash' => $run->corpus_hash,
                'source_url' => $run->corpus?->source_url,
                'scenario' => $run->scenario?->category,
                'model' => $run->target_model,
                'provider' => $run->provider,
                'policy_profile' => $run->policy_profile,
            ],
            'summary' => [
                'total_cases' => $run->total_cases,
                'passed_cases' => $run->passed_cases,
                'failed_cases' => $run->failed_cases,
                'security_score' => $run->total_cases > 0 ? round(($run->passed_cases / $run->total_cases) * 100, 1) : 0,
            ],
            'control_evidence' => $run->results->map(fn ($result) => $this->controlEvidence($result))->values()->all(),
            'findings' => $run->findings->toArray(),
            'limitations' => [
                'A passed test proves only the evaluated case, model, policy, and corpus revision.',
                'Unavailable controls are never counted as effective.',
                'Model-generated remediation remains a proposal until explicitly reviewed.',
            ],
        ];
    }

    private function controlEvidence($result): array
    {
        $trace = $result->trace ?? [];

        return [
            'case_id' => $result->promptCase?->external_id,
            'case_passed' => $result->passed,
            'controls' => [
                'nemo_input' => $this->status(data_get($trace, 'input.nemo'), $result->passed),
                'llm_guard_input' => $this->status(data_get($trace, 'input.llm_guard'), $result->passed),
                'nemo_output' => $this->status(data_get($trace, 'output.nemo'), $result->passed),
                'llm_guard_output' => $this->status(data_get($trace, 'output.llm_guard'), $result->passed),
            ],
        ];
    }

    private function status(?array $control, bool $casePassed): array
    {
        if ($control === null || $control === []) {
            return ['status' => 'not_evaluated', 'evidence' => null];
        }
        if ($control['flagged_for_review'] ?? false) {
            return ['status' => 'unavailable', 'evidence' => $control];
        }
        if (($control['blocked'] ?? false) || ($control['scanners_triggered'] ?? []) !== []) {
            return ['status' => 'effective', 'evidence' => $control];
        }

        return ['status' => $casePassed ? 'not_triggered' : 'bypassed', 'evidence' => $control];
    }
}
