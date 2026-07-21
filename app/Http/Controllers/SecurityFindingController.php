<?php

namespace App\Http\Controllers;

use App\Models\PromptCorpusRun;
use App\Models\RemediationProposal;
use App\Models\SecurityFinding;
use App\Services\SecurityAdvisorService;
use App\Services\SecurityFindingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SecurityFindingController extends Controller
{
    public function materialize(PromptCorpusRun $run, SecurityFindingService $service): JsonResponse
    {
        return response()->json(['findings' => $service->materialize($run)], 201);
    }

    public function propose(SecurityFinding $finding, SecurityAdvisorService $advisor): JsonResponse
    {
        return response()->json(['proposal' => $advisor->propose($finding)], 201);
    }

    public function review(Request $request, RemediationProposal $proposal): JsonResponse
    {
        abort_unless($proposal->status === 'pending_review', 409, 'This proposal has already been reviewed.');

        $data = $request->validate([
            'decision' => ['required', 'in:approved,rejected'],
            'reviewer_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $proposal->update([
            'status' => $data['decision'],
            'reviewer_note' => $data['reviewer_note'] ?? null,
            'reviewed_at' => now(),
        ]);

        $proposal->finding()->update(['status' => $data['decision'] === 'approved' ? 'remediation_approved' : 'open']);

        return response()->json(['proposal' => $proposal->fresh()]);
    }
}
