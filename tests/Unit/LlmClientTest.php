<?php

namespace Tests\Unit;

use App\Services\LlmClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class LlmClientTest extends TestCase
{
    public function test_it_calls_an_openai_compatible_chat_completion_provider(): void
    {
        config()->set('prism.providers.groq', [
            'api_key' => 'groq-test-key',
            'url' => 'https://groq.example.test/openai/v1',
        ]);

        Http::fake([
            'https://groq.example.test/openai/v1/chat/completions' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'Defensive response']],
                ],
                'usage' => [
                    'prompt_tokens' => 17,
                    'completion_tokens' => 9,
                ],
            ]),
        ]);

        $result = app(LlmClient::class)->generate('groq', 'llama-test', [
            ['role' => 'user', 'content' => 'Test this prompt'],
        ], 256);

        $this->assertSame([
            'text' => 'Defensive response',
            'tokens_input' => 17,
            'tokens_output' => 9,
        ], $result);

        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'https://groq.example.test/openai/v1/chat/completions'
                && $request->hasHeader('Authorization', 'Bearer groq-test-key')
                && $request['model'] === 'llama-test'
                && $request['max_tokens'] === 256
                && $request['stream'] === false;
        });
    }

    public function test_it_parses_the_openai_responses_api_shape(): void
    {
        config()->set('prism.providers.openai', [
            'api_key' => 'openai-test-key',
            'url' => 'https://openai.example.test/v1',
        ]);

        Http::fake([
            'https://openai.example.test/v1/responses' => Http::response([
                'output' => [
                    [
                        'content' => [
                            ['type' => 'output_text', 'text' => 'First '],
                            ['type' => 'refusal', 'refusal' => 'ignored'],
                        ],
                    ],
                    [
                        'content' => [
                            ['type' => 'output_text', 'text' => 'second'],
                        ],
                    ],
                ],
                'usage' => [
                    'input_tokens' => 22,
                    'output_tokens' => 11,
                ],
            ]),
        ]);

        $result = app(LlmClient::class)->generate('openai', 'gpt-test', [
            ['role' => 'system', 'content' => 'Review security evidence'],
        ], 512);

        $this->assertSame('First second', $result['text']);
        $this->assertSame(22, $result['tokens_input']);
        $this->assertSame(11, $result['tokens_output']);

        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'https://openai.example.test/v1/responses'
                && $request->hasHeader('Authorization', 'Bearer openai-test-key')
                && $request['model'] === 'gpt-test'
                && $request['max_output_tokens'] === 512;
        });
    }

    public function test_it_reports_provider_failures_without_losing_the_response_body(): void
    {
        config()->set('prism.providers.groq', [
            'api_key' => 'groq-test-key',
            'url' => 'https://groq.example.test/openai/v1',
        ]);

        Http::fake([
            '*' => Http::response(['error' => 'rate limited'], 429),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('LLM provider returned HTTP 429');
        $this->expectExceptionMessage('rate limited');

        app(LlmClient::class)->generate('groq', 'llama-test', [
            ['role' => 'user', 'content' => 'Test this prompt'],
        ]);
    }
}
