<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;

class HealthCheckCommand extends Command
{
    protected $signature = 'arena:health';

    protected $description = 'Check rule adapters, model provider, database, and cache';

    public function handle(): int
    {
        $this->newLine();
        $this->info('Red-Team Arena — Service Health Check');
        $this->line(str_repeat('─', 42));

        $healthy = true;
        $healthy = $this->probe(
            'NeMo-compatible adapter',
            config('services.nemo_guardrails.url', 'http://nemo-guardrails:8000').'/v1/health'
        ) && $healthy;
        $healthy = $this->probe(
            'LLM Guard-compatible adapter',
            config('services.llm_guard.url', 'http://llm-guard:8000').'/health'
        ) && $healthy;

        $groqKey = config('prism.providers.groq.api_key');
        if (empty($groqKey)) {
            $this->components->warn('Groq API: not configured (duels need a model-provider key)');
            $healthy = false;
        } else {
            try {
                $response = Http::timeout(5)
                    ->withToken($groqKey)
                    ->get('https://api.groq.com/openai/v1/models');
                $this->components->{$response->successful() ? 'info' : 'error'}('Groq API: HTTP '.$response->status());
                $healthy = $response->successful() && $healthy;
            } catch (\Throwable $exception) {
                $this->components->error('Groq API: '.$exception->getMessage());
                $healthy = false;
            }
        }

        try {
            DB::connection()->getPdo();
            $this->components->info('PostgreSQL: online');
        } catch (\Throwable $exception) {
            $this->components->error('PostgreSQL: '.$exception->getMessage());
            $healthy = false;
        }

        try {
            Redis::ping();
            $this->components->info('Redis: online');
        } catch (\Throwable $exception) {
            $this->components->error('Redis: '.$exception->getMessage());
            $healthy = false;
        }

        $this->newLine();
        if ($healthy) {
            $this->info('All configured services are ready.');
        } else {
            $this->warn('One or more services need attention.');
            $this->line('Start adapters: docker compose up -d nemo-guardrails llm-guard');
        }

        return $healthy ? self::SUCCESS : self::FAILURE;
    }

    private function probe(string $name, string $url): bool
    {
        try {
            $response = Http::timeout(3)->get($url);
            if ($response->successful()) {
                $engine = $response->json('engine', 'unknown-engine');
                $this->components->info("{$name}: online ({$engine})");
                return true;
            }

            $this->components->error("{$name}: HTTP {$response->status()}");
        } catch (\Throwable $exception) {
            $this->components->error("{$name}: {$exception->getMessage()}");
        }

        return false;
    }
}
