<?php

namespace App\Http\Controllers;

use App\Models\PromptCorpusRun;
use App\Services\DemoSecurityService;
use App\Services\SecurityGateService;
use App\Services\SecurityReportService;
use Illuminate\Http\JsonResponse;

class SecurityEvidenceController extends Controller
{
    public function demo(DemoSecurityService $demo): JsonResponse
    {
        return response()->json(['demo' => $demo->compare()]);
    }

    public function gate(PromptCorpusRun $run, SecurityGateService $gate): JsonResponse
    {
        $result = $gate->evaluate($run);
        return response()->json(['gate' => $result], $result['status'] === 'pass' ? 200 : 422);
    }

    public function report(PromptCorpusRun $run, SecurityReportService $report): JsonResponse
    {
        return response()->json(['report' => $report->build($run)]);
    }
}
