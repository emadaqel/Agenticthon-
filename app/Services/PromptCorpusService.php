<?php

namespace App\Services;

use App\Models\PromptCorpus;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Yaml\Yaml;

class PromptCorpusService
{
    public function assertSupportedSource(string $url): void
    {
        $this->normalizeGithubUrl($url);
    }

    /**
     * Synchronize a public GitHub raw file. Keeping the source URL and SHA makes
     * every future assessment attributable to an exact corpus revision.
     */
    public function sync(PromptCorpus $corpus): array
    {
        $url = $this->normalizeGithubUrl($corpus->source_url);
        $response = Http::accept('application/vnd.github.raw+json')->timeout(15)->get($url);

        if (! $response->successful()) {
            throw ValidationException::withMessages(['source_url' => "Unable to fetch corpus (HTTP {$response->status()})."]);
        }

        $body = $response->body();
        $hash = hash('sha256', $body);
        $document = $this->parse($body, $url);
        $cases = $this->normalizeCases($document);

        if ($cases === []) {
            throw ValidationException::withMessages(['source_url' => 'The corpus did not contain supported Promptfoo tests or a cases array.']);
        }

        DB::transaction(function () use ($corpus, $cases, $hash, $response): void {
            $seen = [];
            foreach ($cases as $case) {
                $seen[] = $case['external_id'];
                $corpus->cases()->updateOrCreate(
                    ['external_id' => $case['external_id']],
                    Arr::except($case, ['external_id'])
                );
            }

            $corpus->cases()->whereNotIn('external_id', $seen)->delete();
            $sourceSha = preg_match('/^[a-f0-9]{40}$/i', (string) $corpus->source_ref)
                ? strtolower($corpus->source_ref)
                : null;
            $corpus->update([
                'content_hash' => $hash,
                'source_sha' => $sourceSha,
                'last_synced_at' => now(),
                'metadata' => array_merge($corpus->metadata ?? [], [
                    'case_count' => count($cases),
                    'canonical_source_url' => $this->normalizeGithubUrl($corpus->source_url),
                    'source_etag' => $response->header('ETag'),
                ]),
            ]);
        });

        return ['cases' => count($cases), 'content_hash' => $hash];
    }

    private function normalizeGithubUrl(string $url): string
    {
        $parts = parse_url($url);
        $host = strtolower($parts['host'] ?? '');

        if (! in_array($host, ['github.com', 'raw.githubusercontent.com'], true)) {
            throw ValidationException::withMessages(['source_url' => 'Only public GitHub file URLs are supported.']);
        }

        if ($host === 'github.com' && preg_match('#^/([^/]+)/([^/]+)/blob/([^/]+)/(.+)$#', $parts['path'] ?? '', $m)) {
            return "https://raw.githubusercontent.com/{$m[1]}/{$m[2]}/{$m[3]}/{$m[4]}";
        }

        if ($host === 'raw.githubusercontent.com') {
            return $url;
        }

        throw ValidationException::withMessages(['source_url' => 'Use a GitHub file URL containing /blob/<ref>/ or a raw.githubusercontent.com URL.']);
    }

    private function parse(string $body, string $url): array
    {
        try {
            if (str_ends_with(strtolower(parse_url($url, PHP_URL_PATH) ?? ''), '.json')) {
                return json_decode($body, true, 512, JSON_THROW_ON_ERROR);
            }

            return Yaml::parse($body) ?? [];
        } catch (\Throwable $exception) {
            throw ValidationException::withMessages(['source_url' => 'The remote corpus could not be parsed: '.$exception->getMessage()]);
        }
    }

    private function normalizeCases(array $document): array
    {
        $cases = $document['cases'] ?? $document['tests'] ?? [];
        $normalized = [];

        foreach ($cases as $index => $case) {
            $vars = $case['vars'] ?? [];
            $prompt = $case['prompt'] ?? $vars['adversarial_prompt'] ?? $vars['prompt'] ?? null;
            if (! is_string($prompt) || trim($prompt) === '') {
                continue;
            }

            $normalized[] = [
                'external_id' => (string) ($case['id'] ?? $case['description'] ?? "case-{$index}"),
                'category' => $case['category'] ?? $case['metadata']['category'] ?? null,
                'prompt' => trim($prompt),
                'expected_policy' => $case['expected_policy'] ?? $case['assert'][0]['value'] ?? null,
                'metadata' => ['description' => $case['description'] ?? null, 'assertions' => $case['assert'] ?? [], 'source_index' => $index],
            ];
        }

        return $normalized;
    }
}
