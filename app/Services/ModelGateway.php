<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Prism\Prism\Prism;
use Prism\Prism\ValueObjects\Messages\UserMessage;
use Prism\Prism\ValueObjects\Messages\SystemMessage;

class ModelGateway
{
    public function call(
        string $prompt,
        string $basePrompt,
        string $targetModel = 'llama3-8b-8192',
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
        $apiKey    = env('HUGGINGFACE_API_KEY', '');

        if (empty($apiKey)) {
            return $this->errorResponse($model, 'huggingface', 'HUGGINGFACE_API_KEY is not configured.', $startTime);
        }

        try {
            $response = Http::withToken($apiKey)
                ->timeout(30)
                ->post("https://api-inference.huggingface.co/models/{$model}", [
                    'inputs' => "System: {$basePrompt}\n\nUser: {$prompt}\n\nAssistant:",
                    'parameters' => [
                        'max_new_tokens'  => 512,
                        'temperature'     => 0.7,
                        'return_full_text' => false,
                    ],
                ]);

            $latency = (int) ((microtime(true) - $startTime) * 1000);

            if ($response->successful()) {
                $body = $response->json();
                $text = is_array($body) ? ($body[0]['generated_text'] ?? 'No response generated.') : ($body['generated_text'] ?? 'No response generated.');

                return [
                    'response'      => trim($text),
                    'model'         => $model,
                    'provider'      => 'huggingface',
                    'latency_ms'    => $latency,
                    'tokens_input'  => 0,
                    'tokens_output' => 0,
                ];
            }

            return $this->errorResponse($model, 'huggingface', "HuggingFace API error: HTTP {$response->status()}", $startTime);

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
