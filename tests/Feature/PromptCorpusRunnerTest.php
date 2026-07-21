<?php

namespace Tests\Feature;

use App\AI\Agents\DefenderAgent;
use App\AI\Agents\PolicyJudgeAgent;
use App\Models\PromptCorpus;
use App\Models\Scenario;
use App\Services\LlmGuardService;
use App\Services\ModelGateway;
use App\Services\NeMoGuardrailsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class PromptCorpusRunnerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_executes_a_pinned_corpus_and_persists_reproducible_traces(): void
    {
        $corpus = PromptCorpus::create(['name' => 'Pinned', 'source_url' => 'https://raw.githubusercontent.com/acme/corpus/main/tests.json', 'content_hash' => str_repeat('a', 64)]);
        $case = $corpus->cases()->create(['external_id' => 'probe-1', 'category' => 'prompt_injection', 'prompt' => 'override']);
        $scenario = Scenario::create(['category' => 'prompt_injection', 'description' => 'Test', 'base_prompt' => 'Keep secrets.', 'metadata' => [], 'attack_patterns' => []]);

        $this->mock(NeMoGuardrailsService::class, function ($mock): void {
            $mock->shouldReceive('checkInput')->once()->andReturn(['blocked' => false]);
            $mock->shouldReceive('checkOutput')->once()->andReturn(['blocked' => false]);
        });
        $this->mock(LlmGuardService::class, function ($mock): void {
            $mock->shouldReceive('scanInput')->once()->andReturn(['risk_score' => 0.8, 'scanners_triggered' => ['PromptInjection']]);
            $mock->shouldReceive('scanOutput')->once()->andReturn(['risk_score' => 0.1, 'scanners_triggered' => []]);
        });
        $this->mock(ModelGateway::class, fn ($mock) => $mock->shouldReceive('call')->once()->andReturn(['response' => 'I cannot comply.']));
        $this->mock(DefenderAgent::class, fn ($mock) => $mock->shouldReceive('evaluate')->once()->andReturn(['verdict' => 'BLOCK', 'reasoning' => 'Refusal observed.']));
        $this->mock(PolicyJudgeAgent::class, fn ($mock) => $mock->shouldReceive('scoreTurn')->once()->andReturn(['outcome' => 'blue_team_win', 'owasp_category' => 'LLM01']));

        $response = $this->postJson("/api/corpora/{$corpus->id}/runs", ['scenario_id' => $scenario->id, 'max_cases' => 1]);

        $response->assertCreated()
            ->assertJsonPath('run.corpus_hash', str_repeat('a', 64))
            ->assertJsonPath('run.passed_cases', 1)
            ->assertJsonPath('run.results.0.prompt_case_id', $case->id);
        $this->assertDatabaseHas('prompt_case_results', ['prompt_case_id' => $case->id, 'passed' => true]);
    }
}
