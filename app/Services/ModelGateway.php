<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Prism\Prism\Facades\Prism;
use Prism\Prism\ValueObjects\Messages\UserMessage;
use Prism\Prism\ValueObjects\Messages\SystemMessage;

class ModelGateway
{
    public function call(
        string $prompt,
        string $basePrompt,
        string $targetModel = 'llama-3.1-8b-instant',
        string $provider    = 'groq',
    ): array {
        if ($provider === 'huggingface') {
            return $this->callHuggingFace($prompt, $basePrompt, $targetModel);
        }

        return $this->callPrism($prompt, $basePrompt, $targetModel, $provider);
    }

    private function callPrism(string $prompt, string $basePrompt, string $model, string $provider): array
    {
        $startTime = microtime(true);

        try {
            $response = Prism::text()
                ->using($provider, $model)
                ->withMessages([
                    new SystemMessage($basePrompt),
                    new UserMessage($prompt),
                ])
                ->withMaxTokens(1024)
                ->generate();

            return [
                'response'       => $response->text,
                'model'          => $model,
                'provider'       => $provider,
                'latency_ms'     => (int) ((microtime(true) - $startTime) * 1000),
                'tokens_input'   => $response->usage->promptTokens ?? 0,
                'tokens_output'  => $response->usage->completionTokens ?? 0,
            ];
        } catch (\Exception $e) {
            return $this->errorResponse($model, $provider, $e->getMessage(), $startTime);
        }
    }

    private function callHuggingFace(string $prompt, string $basePrompt, string $model): array
    {
        $startTime = microtime(true);
        $apiKey    = config('services.huggingface.api_key', env('HUGGINGFACE_API_KEY', ''));

        if (empty($apiKey)) {
            return $this->errorResponse($model, 'huggingface', 'HUGGINGFACE_API_KEY is not configured.', $startTime);
        }

        // Strip ":provider" suffix to get a bare model ID for serverless fallback.
        // The router expects the FULL name (including suffix) in the payload.
        $hasProviderSuffix = (bool) preg_match('/^(.+):([a-z0-9_-]+)$/i', $model, $m);
        $bareModelId       = $hasProviderSuffix ? $m[1] : $model;

        $messages = [
            ['role' => 'system', 'content' => $basePrompt],
            ['role' => 'user',   'content' => $prompt],
        ];

        // ── 1. HF Inference Router ────────────────────────────────────────────
        // URL: https://router.huggingface.co/v1/chat/completions
        // Model field carries the full name, e.g. "Qwen/Qwen2.5-7B-Instruct:together"
        // or bare "moonshotai/Kimi-K2-Instruct-0905" — the router resolves routing.
        try {
            $response = Http::withToken($apiKey)
                ->timeout(90)
                ->post('https://router.huggingface.co/v1/chat/completions', [
                    'model'       => $model,
                    'messages'    => $messages,
                    'max_tokens'  => 512,
                    'temperature' => 0.7,
                    'stream'      => false,
                ]);

            $latency = (int) ((microtime(true) - $startTime) * 1000);

            if ($response->successful()) {
                $body = $response->json();
                $text = $body['choices'][0]['message']['content'] ?? null;
                if ($text !== null) {
                    return [
                        'response'      => trim($text),
                        'model'         => $model,
                        'provider'      => 'huggingface',
                        'latency_ms'    => $latency,
                        'tokens_input'  => $body['usage']['prompt_tokens'] ?? 0,
                        'tokens_output' => $body['usage']['completion_tokens'] ?? 0,
                    ];
                }
            }
        } catch (\Exception) {
            // fall through to serverless endpoint
        }

        // ── 2. HF Serverless chat completions (modern instruct models) ────────
        try {
            $url      = "https://api-inference.huggingface.co/models/{$bareModelId}/v1/chat/completions";
            $response = Http::withToken($apiKey)->timeout(60)->post($url, [
                'model'       => $bareModelId,
                'messages'    => $messages,
                'max_tokens'  => 512,
                'temperature' => 0.7,
                'stream'      => false,
            ]);

            $latency = (int) ((microtime(true) - $startTime) * 1000);

            if ($response->successful()) {
                $body = $response->json();
                $text = $body['choices'][0]['message']['content'] ?? null;
                if ($text !== null) {
                    return [
                        'response'      => trim($text),
                        'model'         => $model,
                        'provider'      => 'huggingface',
                        'latency_ms'    => $latency,
                        'tokens_input'  => $body['usage']['prompt_tokens'] ?? 0,
                        'tokens_output' => $body['usage']['completion_tokens'] ?? 0,
                    ];
                }
            }
        } catch (\Exception) {
            // fall through to classic endpoint
        }

        // ── 3. Classic text-generation fallback ──────────────────────────────
        try {
            $response = Http::withToken($apiKey)->timeout(60)
                ->post("https://api-inference.huggingface.co/models/{$bareModelId}", [
                    'inputs'     => "System: {$basePrompt}\n\nUser: {$prompt}\n\nAssistant:",
                    'parameters' => ['max_new_tokens' => 512, 'temperature' => 0.7, 'return_full_text' => false],
                ]);

            $latency = (int) ((microtime(true) - $startTime) * 1000);

            if ($response->successful()) {
                $body = $response->json();
                $text = is_array($body) ? ($body[0]['generated_text'] ?? '') : ($body['generated_text'] ?? '');
                return [
                    'response'      => trim($text) ?: 'No response generated.',
                    'model'         => $model,
                    'provider'      => 'huggingface',
                    'latency_ms'    => $latency,
                    'tokens_input'  => 0,
                    'tokens_output' => 0,
                ];
            }

            return $this->errorResponse($model, 'huggingface', "HF API HTTP {$response->status()}: " . $response->body(), $startTime);
        } catch (\Exception $e) {
            return $this->errorResponse($model, 'huggingface', $e->getMessage(), $startTime);
        }
    }

    private function errorResponse(string $model, string $provider, string $message, float $startTime): array
    {
        return [
            'response'      => "Error: {$message}",
            'model'         => $model,
            'provider'      => $provider,
            'latency_ms'    => (int) ((microtime(true) - $startTime) * 1000),
            'tokens_input'  => 0,
            'tokens_output' => 0,
        ];
    }
}
