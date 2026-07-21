<?php

namespace App\Http\Controllers;

use App\Models\AttackSurfaceAssessment;
use App\Services\AttackPathAnalyzer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttackSurfaceController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['assessments' => AttackSurfaceAssessment::latest()->get()]);
    }

    public function store(Request $request, AttackPathAnalyzer $analyzer): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'components' => ['required', 'array', 'min:2', 'max:100'],
            'components.*.id' => ['required', 'string', 'max:80', 'distinct'],
            'components.*.name' => ['required', 'string', 'max:120'],
            'components.*.type' => ['required', 'in:input,model,tool,data,guardrail,output'],
            'components.*.trust' => ['nullable', 'in:untrusted,trusted'],
            'components.*.sensitivity' => ['nullable', 'in:public,internal,sensitive'],
            'components.*.permissions' => ['nullable', 'array'],
            'components.*.permissions.*' => ['string', 'max:120'],
            'connections' => ['required', 'array', 'min:1', 'max:200'],
            'connections.*.from' => ['required', 'string'],
            'connections.*.to' => ['required', 'string'],
            'connections.*.approval_required' => ['nullable', 'boolean'],
            'connections.*.sanitized' => ['nullable', 'boolean'],
        ]);

        return response()->json([
            'assessment' => $analyzer->analyze($data['name'], $data['components'], $data['connections']),
        ], 201);
    }

    public function show(AttackSurfaceAssessment $assessment): JsonResponse
    {
        return response()->json(['assessment' => $assessment]);
    }
}
