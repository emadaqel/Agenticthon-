<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * NeMo Guardrails HTTP client.
 *
 * Connects to the self-hosted NeMo Guardrails container (Phase 2).
 * On connection failure or timeout → treats as "allow" and flags for human review.
 * Configure the URL via: NEMO_GUARDRAILS_URL in .env
 */
class NeMoGuardrailsService
{
    protected string $baseUrl;
    protected int    $timeout;

    public function __construct()
    {
        $this->baseUrl = config('services.nemo_guardrails.url', env('NEMO_GUARDRAILS_URL', 'http://nemo-guardrails:8000'));
        $this->timeout = 2; // 2 seconds per global rule
    }

    /**
     * Check the attacker's input prompt before it reaches the target model.
     *
     * @return array{blocked:bool, rail_triggered:string|null, explanation:string, flagged_for_review:bool}
     */
    public function checkInput(string $prompt, string $policy = 'strict'): array
    {
        return $this->call('/v1/rails/input', ['prompt' => $prompt, 'policy' => $policy]);
    }

    /**
     * Check the model's response before it reaches the defender/user.
     *
     * @return array{blocked:bool, rail_triggered:string|null, explanation:string, flagged_for_review:bool}
     */
    public function checkOutput(string $response, string $policy = 'strict'): array
    {
        return $this->call('/v1/rails/output', ['response' => $response, 'policy' => $policy]);
    }

    protected function call(string $endpoint, array $payload): array
    {
        try {
            $res = Http::timeout($this->timeout)
                ->baseUrl($this->baseUrl)
                ->acceptJson()
                ->post($endpoint, $payload);

            if ($res->successful()) {
                $body = $res->json();

                return [
                    'blocked'           => $body['blocked']        ?? false,
                    'rail_triggered'    => $body['rail_triggered'] ?? null,
                    'explanation'       => $body['explanation']    ?? 'No explanation provided.',
                    'flagged_for_review'=> false,
                ];
            }

            Log::warning('NeMoGuardrailsService non-200 response', [
                'status'   => $res->status(),
                'endpoint' => $endpoint,
            ]);

            return $this->passThrough("NeMo returned HTTP {$res->status()} — treated as allow.");

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::warning('NeMoGuardrailsService connection failed — container may not be running.', [
                'url'   => $this->baseUrl . $endpoint,
                'error' => $e->getMessage(),
            ]);
            return $this->passThrough('NeMo Guardrails not reachable — treated as allow, flagged for review.', true);

        } catch (\Exception $e) {
            Log::error('NeMoGuardrailsService unexpected error', ['error' => $e->getMessage()]);
            return $this->passThrough('Unexpected NeMo error — treated as allow, flagged for review.', true);
        }
    }

    private function passThrough(string $explanation, bool $flagged = false): array
    {
        return [
            'blocked'            => false,
            'rail_triggered'     => null,
            'explanation'        => $explanation,
            'flagged_for_review' => $flagged,
        ];
    }
}
