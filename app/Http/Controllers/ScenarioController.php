<?php

namespace App\Http\Controllers;

use App\Models\Scenario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ScenarioController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Scenario::all());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category'        => 'required|string|max:80',
            'description'     => 'required|string|max:500',
            'base_prompt'     => 'required|string|max:4000',
            'severity'        => 'required|in:LOW,MEDIUM,HIGH,CRITICAL',
            'attack_patterns' => 'required|array|min:1',
            'attack_patterns.*' => 'string|max:80',
            'owasp_category'  => 'nullable|string|max:10',
        ]);

        $scenario = Scenario::create([
            'category'        => Str::slug($validated['category'], '_'),
            'description'     => $validated['description'],
            'base_prompt'     => $validated['base_prompt'],
            'attack_patterns' => $validated['attack_patterns'],
            'metadata'        => [
                'severity'       => $validated['severity'],
                'owasp_category' => $validated['owasp_category'] ?? 'LLM01',
                'custom'         => true,
            ],
        ]);

        return response()->json([
            'scenario' => $scenario,
            'message'  => 'Scenario created successfully.',
        ], 201);
    }

    public function destroy(Scenario $scenario): JsonResponse
    {
        $scenario->delete();
        return response()->json(['message' => 'Scenario deleted.']);
    }
}
