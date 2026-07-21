<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * LLM Guard REST API client.
 *
 * Connects to the self-hosted LLM Guard container (Phase 2).
 * On connection failure or timeout → returns risk_score: 0.0 and flags the turn.
 * Configure the URL via: LLM_GUARD_URL in .env
 *
 * Input scanners:  PromptInjection, Toxicity, Secrets, BanTopics, TokenLimit, Language
 * Output scanners: NoRefusal, Sensitive, Toxicity, FactualConsistency, PIIAnonymizer, BanSubstrings
 */
class LlmGuardService
{
    protected string $baseUrl;
    protected int    $timeout;

    public function __construct()
    {
        $this->baseUrl = config('services.llm_guard.url', env('LLM_GUARD_URL', 'http://llm-guard:8000'));
        $this->timeout = 2;
    }

    /**
     * Scan an attacker's input prompt.
     *
     * @return array{risk_score:float, scanners_triggered:string[], sanitized_text:string, flagged_for_review:bool}
     */
    public function scanInput(string $prompt, string $policy = 'strict'): array
    {
        return $this->call('/scan/prompt', ['prompt' => $prompt, 'policy' => $policy], $prompt);
    }

    /**
     * Scan the model's output response.
     *
     * @return array{risk_score:float, scanners_triggered:string[], sanitized_text:string, flagged_for_review:bool}
     */
    public function scanOutput(string $response, string $policy = 'strict'): array
    {
        return $this->call('/scan/output', ['output' => $response, 'policy' => $policy], $response);
    }

    protected function call(string $endpoint, array $payload, string $originalText): array
    {
        try {
            $res = Http::timeout($this->timeout)
                ->baseUrl($this->baseUrl)
                ->acceptJson()
                ->post($endpoint, $payload);

            if ($res->successful()) {
                $body = $res->json();

                return [
                    'risk_score'         => (float) ($body['risk_score'] ?? 0.0),
                    'scanners_triggered' => $body['scanners_triggered'] ?? [],
                    'sanitized_text'     => $body['sanitized_text'] ?? $originalText,
                    'flagged_for_review' => false,
                ];
            }

            Log::warning('LlmGuardService non-200 response', [
                'status'   => $res->status(),
                'endpoint' => $endpoint,
            ]);

            return $this->clean($originalText, "LLM Guard returned HTTP {$res->status()}.");

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::warning('LlmGuardService connection failed — container may not be running.', [
                'url'   => $this->baseUrl . $endpoint,
                'error' => $e->getMessage(),
            ]);
            return $this->clean($originalText, 'LLM Guard not reachable — risk_score set to 0.0, flagged for review.', true);

        } catch (\Exception $e) {
            Log::error('LlmGuardService unexpected error', ['error' => $e->getMessage()]);
            return $this->clean($originalText, 'Unexpected LLM Guard error.', true);
        }
    }

    private function clean(string $text, string $note = '', bool $flagged = false): array
    {
        return [
            'risk_score'         => 0.0,
            'scanners_triggered' => [],
            'sanitized_text'     => $text,
            'flagged_for_review' => $flagged,
        ];
    }
}
