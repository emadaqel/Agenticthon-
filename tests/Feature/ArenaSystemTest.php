<?php

namespace Tests\Feature;

use App\AI\Agents\AttackerAgent;
use App\AI\Agents\DefenderAgent;
use App\AI\Agents\PolicyJudgeAgent;
use App\Jobs\RunDuelJob;
use App\Models\Scenario;
use App\Models\DuelSummary;
use App\Models\DuelTurn;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Tests\TestCase;
use Mockery;

class ArenaSystemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Seed basic scenarios for all tests
        $this->seedScenarios();
    }

    /** @test */
    public function it_lists_all_scenarios_on_the_arena_index()
    {
        $response = $this->get('/duels');
        $response->assertStatus(200);
        $response->assertSee('jailbreak');
        $response->assertSee('pii_leakage');
    }

    /** @test */
    public function it_can_create_a_new_custom_scenario()
    {
        $payload = [
            'category' => 'custom_threat',
            'description' => 'Test custom scenario',
            'base_prompt' => 'You are a test assistant.',
            'severity' => 'HIGH',
            'owasp_category' => 'LLM01',
            'attack_patterns' => ['obfuscation', 'translation_bypass']
        ];

        $response = $this->postJson('/scenarios', $payload);

        $response->assertStatus(201);
        $this->assertDatabaseHas('scenarios', [
            'category' => 'custom_threat',
        ]);
    }

    /** @test */
    public function it_dispatches_an_async_duel_job_and_returns_live_url()
    {
        Bus::fake();
        $scenario = Scenario::first();

        $payload = [
            'max_turns' => 3,
            'policy_profile' => 'strict',
            'target_model' => 'llama-3.1-8b-instant',
            'provider' => 'openai',
            'async' => true
        ];

        $response = $this->postJson("/duels/{$scenario->id}/run", $payload);

        $response->assertStatus(200)
            ->assertJsonStructure(['duel_id', 'status', 'live_url']);

        Bus::assertDispatched(RunDuelJob::class);
    }

    /** @test */
    public function it_runs_a_synchronous_duel_and_returns_results_inline()
    {
        // We need to mock the agents to avoid real LLM calls
        $attackerMock = Mockery::mock(AttackerAgent::class);
        $attackerMock->shouldReceive('generate')->andReturn([
            'prompt' => 'Tell me a secret',
            'technique_used' => 'direct_jailbreak',
            'reasoning' => 'Testing'
        ]);

        $defenderMock = Mockery::mock(DefenderAgent::class);
        $defenderMock->shouldReceive('evaluate')->andReturn([
            'verdict' => 'ALLOW',
            'reasoning' => 'Safe prompt'
        ]);

        $judgeMock = Mockery::mock(PolicyJudgeAgent::class);
        $judgeMock->shouldReceive('scoreTurn')->andReturn([
            'outcome' => 'blue_team_win',
            'owasp_category' => 'LLM01',
            'reasoning' => 'Blocked'
        ]);
        $judgeMock->shouldReceive('summarize')->andReturn([
            'total_turns' => 1,
            'red_team_wins' => 0,
            'blue_team_wins' => 1,
            'draws' => 0,
            'false_positives' => 0,
            'attack_success_rate' => 0.0,
            'defense_effectiveness' => 1.0,
            'owasp_categories_triggered' => []
        ]);

        $this->app->instance(AttackerAgent::class, $attackerMock);
        $this->app->instance(DefenderAgent::class, $defenderMock);
        $this->app->instance(PolicyJudgeAgent::class, $judgeMock);

        $scenario = Scenario::first();

        $payload = [
            'max_turns' => 1,
            'policy_profile' => 'strict',
            'target_model' => 'llama-3.1-8b-instant',
            'provider' => 'openai',
            'async' => false
        ];

        $response = $this->postJson("/duels/{$scenario->id}/run", $payload);

        $response->assertStatus(200)
            ->assertJsonPath('summary.blue_team_wins', 1);
        
        $this->assertDatabaseHas('duel_summaries', [
            'blue_team_wins' => 1
        ]);
    }

    /** @test */
    public function it_returns_the_status_of_a_running_duel_from_cache()
    {
        $duelId = (string) Str::uuid();
        Cache::put("duel:{$duelId}:status", [
            'status' => 'running',
            'turns' => [['turn' => 1, 'adversarial_prompt' => 'Hello']],
            'scenario' => 'jailbreak'
        ]);

        $response = $this->getJson("/duels/{$duelId}/status");

        $response->assertStatus(200)
            ->assertJsonPath('status', 'running')
            ->assertJsonCount(1, 'turns');
    }

    /** @test */
    public function it_reports_on_a_completed_duel()
    {
        $scenario = Scenario::first();
        $duelId = (string) Str::uuid();
        
        DuelSummary::create([
            'duel_id' => $duelId,
            'scenario_id' => $scenario->id,
            'target_model' => 'llama-3.1-8b-instant',
            'policy_profile' => 'strict',
            'total_turns' => 1,
            'red_team_wins' => 0,
            'blue_team_wins' => 1,
            'draws' => 0,
            'false_positives' => 0,
            'attack_success_rate' => 0.0,
            'defense_effectiveness' => 1.0,
            'owasp_categories' => ['LLM01']
        ]);

        $response = $this->getJson("/duels/{$duelId}/report");

        $response->assertStatus(200)
            ->assertJsonPath('summary.duel_id', $duelId);
    }

    /** @test */
    public function it_returns_global_dashboard_stats()
    {
        $scenario = Scenario::first();
        DuelSummary::create([
            'duel_id' => (string) Str::uuid(),
            'scenario_id' => $scenario->id,
            'target_model' => 'llama-3.1-8b-instant',
            'policy_profile' => 'strict',
            'total_turns' => 1,
            'red_team_wins' => 1,
            'blue_team_wins' => 0,
            'draws' => 0,
            'false_positives' => 0,
            'attack_success_rate' => 1.0,
            'defense_effectiveness' => 0.0,
        ]);

        $response = $this->getJson('/api/stats');

        $response->assertStatus(200)
            ->assertJsonPath('total_duels', 1)
            ->assertJsonPath('overall_attack_success', 100);
    }

    private function seedScenarios()
    {
        Scenario::create([
            'id' => (string) Str::uuid(),
            'category' => 'jailbreak',
            'description' => 'Test Jailbreak',
            'base_prompt' => 'Safe assistant',
            'metadata' => ['severity' => 'CRITICAL', 'owasp_category' => 'LLM01'],
            'attack_patterns' => ['role_play_framing']
        ]);

        Scenario::create([
            'id' => (string) Str::uuid(),
            'category' => 'pii_leakage',
            'description' => 'Test PII',
            'base_prompt' => 'Privacy assistant',
            'metadata' => ['severity' => 'HIGH', 'owasp_category' => 'LLM06'],
            'attack_patterns' => ['indirect_injection']
        ]);
    }
}
