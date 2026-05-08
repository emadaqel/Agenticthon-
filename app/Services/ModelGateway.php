<?php

namespace App\Services;

use Prism\Prism\Prism;
use Prism\Prism\ValueObjects\Messages\UserMessage;
use Prism\Prism\ValueObjects\Messages\SystemMessage;

class ModelGateway
{
    public function call(string $prompt, string $basePrompt, string $targetModel = 'llama3-8b-8192', string $provider = 'groq'): array
    {
        $startTime = microtime(true);

        try {
            $response = Prism::text()
                ->using($provider, $targetModel)
                ->withMessages([
                    new SystemMessage($basePrompt),
                    new UserMessage($prompt)
                ])
                ->withMaxTokens(1024)
                ->generate();

            $latency = (int) ((microtime(true) - $startTime) * 1000);

            return [
                'response' => $response->text,
                'model' => $targetModel,
                'provider' => $provider,
                'latency_ms' => $latency,
                'tokens_input' => $response->usage->promptTokens ?? 0,
                'tokens_output' => $response->usage->completionTokens ?? 0,
            ];
        } catch (\Exception $e) {
            return [
                'response' => 'Error: ' . $e->getMessage(),
                'model' => $targetModel,
                'provider' => $provider,
                'latency_ms' => (int) ((microtime(true) - $startTime) * 1000),
                'tokens_input' => 0,
                'tokens_output' => 0,
            ];
        }
    }
}
