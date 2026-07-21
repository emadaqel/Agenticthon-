<?php

namespace App\Http\Controllers;

use App\Models\PromptCorpus;
use App\Models\PromptCorpusRun;
use App\Models\Scenario;
use App\Services\PromptCorpusService;
use App\Services\PromptCorpusRunner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PromptCorpusController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['corpora' => PromptCorpus::withCount('cases')->latest()->get()]);
    }

    public function store(Request $request, PromptCorpusService $service): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'source_url' => ['required', 'url', 'max:2048'],
            'source_ref' => ['nullable', 'string', 'max:120'],
        ]);

        $service->assertSupportedSource($data['source_url']);
        $corpus = PromptCorpus::create($data);
        $result = $service->sync($corpus);

        return response()->json(['corpus' => $corpus->fresh()->loadCount('cases'), 'sync' => $result], 201);
    }

    public function sync(PromptCorpus $corpus, PromptCorpusService $service): JsonResponse
    {
        return response()->json(['corpus' => $corpus->fresh(), 'sync' => $service->sync($corpus)]);
    }

    public function run(Request $request, PromptCorpus $corpus, PromptCorpusRunner $runner): JsonResponse
    {
        $data = $request->validate([
            'scenario_id' => ['required', 'uuid', 'exists:scenarios,id'],
            'target_model' => ['nullable', 'string', 'max:200'],
            'provider' => ['nullable', 'in:groq,openai,huggingface'],
            'policy_profile' => ['nullable', 'in:strict,moderate,permissive'],
            'max_cases' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $run = $runner->run($corpus, Scenario::findOrFail($data['scenario_id']), $data);

        return response()->json(['run' => $run], 201);
    }

    public function showRun(PromptCorpusRun $run): JsonResponse
    {
        return response()->json(['run' => $run->load('corpus', 'scenario', 'results.promptCase')]);
    }
}
