<?php

namespace App\Services;

use App\Models\PromptCorpusRun;

class SecurityGateService
{
    public function evaluate(PromptCorpusRun $run): array
    {
        $failed = $run->failed_cases;
        $critical = $run->findings()->where('severity', 'CRITICAL')->count();
        $unavailable = $run->results->filter(function ($result): bool {
            $trace = $result->trace ?? [];
            return (bool) data_get($trace, 'input.nemo.flagged_for_review')
                || (bool) data_get($trace, 'input.llm_guard.flagged_for_review')
                || (bool) data_get($trace, 'output.nemo.flagged_for_review')
                || (bool) data_get($trace, 'output.llm_guard.flagged_for_review');
        })->count();

        $violations = [];
        if ($failed > config('security.ci.max_failed_cases', 0)) {
            $violations[] = "{$failed} failed cases exceed the allowed threshold.";
        }
        if ($critical > config('security.ci.max_critical_findings', 0)) {
            $violations[] = "{$critical} critical findings exceed the allowed threshold.";
        }
        if ($unavailable > 0) {
            $violations[] = "{$unavailable} cases ran while at least one security control was unavailable.";
        }

        return [
            'status' => $violations === [] ? 'pass' : 'fail',
            'run_id' => $run->id,
            'corpus_hash' => $run->corpus_hash,
            'metrics' => ['total_cases' => $run->total_cases, 'failed_cases' => $failed, 'critical_findings' => $critical, 'control_unavailable_cases' => $unavailable],
            'thresholds' => ['max_failed_cases' => config('security.ci.max_failed_cases', 0), 'max_critical_findings' => config('security.ci.max_critical_findings', 0)],
            'violations' => $violations,
        ];
    }
}
