<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class LlmClient
{
    public function generate(string $provider, string $model, array $messages, int $maxTokens = 1024): array
    {
        $apiKey = (string) config("prism.providers.{$provider}.api_key", '');
        $baseUrl = rtrim((string) config("prism.providers.{$provider}.url", ''), '/');

        if ($apiKey === '' || $baseUrl === '') {
            throw new RuntimeException(strtoupper($provider).' API configuration is missing.');
        }

        if ($provider === 'openai') {
            return $this->openAiResponses($baseUrl, $apiKey, $model, $messages, $maxTokens);
        }

        return $this->chatCompletions($baseUrl, $apiKey, $model, $messages, $maxTokens);
    }

    private function chatCompletions(string $baseUrl, string $apiKey, string $model, array $messages, int $maxTokens): array
    {
        $response = Http::withToken($apiKey)
            ->acceptJson()
            ->timeout((int) config('prism.request_timeout', 30))
            ->post($baseUrl.'/chat/completions', [
                'model' => $model,
                'messages' => $messages,
                'max_tokens' => $maxTokens,
                'temperature' => 0.2,
                'stream' => false,
            ]);

        $this->ensureSuccess($response);

        return [
            'text' => (string) $response->json('choices.0.message.content', ''),
            'tokens_input' => (int) $response->json('usage.prompt_tokens', 0),
            'tokens_output' => (int) $response->json('usage.completion_tokens', 0),
        ];
    }

    private function openAiResponses(string $baseUrl, string $apiKey, string $model, array $messages, int $maxTokens): array
    {
        $response = Http::withToken($apiKey)
            ->acceptJson()
            ->timeout((int) config('prism.request_timeout', 30))
            ->post($baseUrl.'/responses', [
                'model' => $model,
                'input' => $messages,
                'max_output_tokens' => $maxTokens,
            ]);

        $this->ensureSuccess($response);

        $text = '';
        foreach ((array) $response->json('output', []) as $output) {
            foreach ((array) ($output['content'] ?? []) as $content) {
                if (($content['type'] ?? null) === 'output_text') {
                    $text .= (string) ($content['text'] ?? '');
                }
            }
        }

        return [
            'text' => $text,
            'tokens_input' => (int) $response->json('usage.input_tokens', 0),
            'tokens_output' => (int) $response->json('usage.output_tokens', 0),
        ];
    }

    private function ensureSuccess(Response $response): void
    {
        if (! $response->successful()) {
            throw new RuntimeException("LLM provider returned HTTP {$response->status()}: ".$response->body());
        }
    }
}
