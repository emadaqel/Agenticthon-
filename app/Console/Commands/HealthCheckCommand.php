<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class HealthCheckCommand extends Command
{
    protected $signature   = 'arena:health';
    protected $description = 'Check the health of all Red-Team Arena services (NeMo Guardrails, LLM Guard, Groq API)';

    public function handle(): int
    {
        $this->info('');
        $this->info('⚔  Red-Team Arena — Service Health Check');
        $this->line('─────────────────────────────────────────');
        $this->info('');

        $allHealthy = true;

        // ── NeMo Guardrails ──────────────────────────────────────────────
        $nemoUrl = config('services.nemo_guardrails.url', 'http://nemo-guardrails:8000');
        try {
            $response = Http::timeout(3)->get("{$nemoUrl}/v1/rails/configs");
            if ($response->successful()) {
                $this->line('  ✅  NeMo Guardrails    ' . $nemoUrl);
            } else {
                $this->line('  ⚠️  NeMo Guardrails    ' . $nemoUrl . ' (HTTP ' . $response->status() . ')');
                $allHealthy = false;
            }
        } catch (\Exception $e) {
            $this->line('  ❌  NeMo Guardrails    ' . $nemoUrl . ' — ' . $e->getMessage());
            $allHealthy = false;
        }

        // ── LLM Guard ────────────────────────────────────────────────────
        $guardUrl = config('services.llm_guard.url', 'http://llm-guard:8001');
        try {
            $response = Http::timeout(3)->get("{$guardUrl}/healthz");
            if ($response->successful()) {
                $this->line('  ✅  LLM Guard          ' . $guardUrl);
            } else {
                $this->line('  ⚠️  LLM Guard          ' . $guardUrl . ' (HTTP ' . $response->status() . ')');
                $allHealthy = false;
            }
        } catch (\Exception $e) {
            $this->line('  ❌  LLM Guard          ' . $guardUrl . ' — ' . $e->getMessage());
            $allHealthy = false;
        }

        // ── Groq API ─────────────────────────────────────────────────────
        $groqKey = config('prism.providers.groq.api_key');
        if (empty($groqKey)) {
            $this->line('  ❌  Groq API            No GROQ_API_KEY configured');
            $allHealthy = false;
        } else {
            try {
                $response = Http::timeout(5)
                    ->withHeaders(['Authorization' => "Bearer {$groqKey}"])
                    ->get('https://api.groq.com/openai/v1/models');
                if ($response->successful()) {
                    $models = collect($response->json('data', []))->pluck('id')->take(3)->join(', ');
                    $this->line('  ✅  Groq API            Connected (' . $models . '...)');
                } else {
                    $this->line('  ⚠️  Groq API            HTTP ' . $response->status());
                    $allHealthy = false;
                }
            } catch (\Exception $e) {
                $this->line('  ❌  Groq API            ' . $e->getMessage());
                $allHealthy = false;
            }
        }

        // ── PostgreSQL ───────────────────────────────────────────────────
        try {
            \DB::connection()->getPdo();
            $this->line('  ✅  PostgreSQL          ' . config('database.connections.pgsql.host', 'localhost'));
        } catch (\Exception $e) {
            $this->line('  ❌  PostgreSQL          ' . $e->getMessage());
            $allHealthy = false;
        }

        // ── Redis ────────────────────────────────────────────────────────
        try {
            \Illuminate\Support\Facades\Redis::ping();
            $this->line('  ✅  Redis               ' . config('database.redis.default.host', 'localhost'));
        } catch (\Exception $e) {
            $this->line('  ❌  Redis               ' . $e->getMessage());
            $allHealthy = false;
        }

        $this->info('');
        $this->line('─────────────────────────────────────────');
        if ($allHealthy) {
            $this->info('  All services healthy. Ready to duel! ⚡');
        } else {
            $this->warn('  Some services are unavailable. Duels will run in degraded mode.');
            $this->line('  Tip: Start guardrails with: docker compose --profile guardrails up -d');
        }
        $this->info('');

        return $allHealthy ? self::SUCCESS : self::FAILURE;
    }
}
