<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;

class HealthController extends Controller
{
    public function check(): JsonResponse
    {
        $nemoUrl  = config('services.nemo_guardrails.url', 'http://nemo-guardrails:8000');
        $guardUrl = config('services.llm_guard.url', 'http://llm-guard:8000');
        $openaiKey = config('prism.providers.openai.api_key', '');
        $hfKey     = config('services.huggingface.api_key', '');

        return response()->json([
            'services' => [
                'nemo_guardrails' => [
                    'name'   => 'NeMo Guardrails',
                    'status' => $this->ping($nemoUrl . '/v1/health'),
                    'url'    => $nemoUrl,
                ],
                'llm_guard' => [
                    'name'   => 'LLM Guard',
                    'status' => $this->ping($guardUrl . '/health'),
                    'url'    => $guardUrl,
                ],
                'huggingface' => [
                    'name'   => 'Hugging Face',
                    'status' => ($hfKey && strlen($hfKey) > 8) ? 'configured' : 'not_configured',
                ],
                'database' => [
                    'name'   => 'Database',
                    'status' => $this->checkDatabase(),
                ],
            ],
            'checked_at' => now()->toIso8601String(),
        ]);
    }

    private function ping(string $url): string
    {
        try {
            $res = Http::timeout(2)->get($url);
            return $res->successful() ? 'online' : 'degraded';
        } catch (\Exception) {
            return 'offline';
        }
    }

    private function checkDatabase(): string
    {
        try {
            \DB::connection()->getPdo();
            return 'online';
        } catch (\Exception) {
            return 'offline';
        }
    }
}
