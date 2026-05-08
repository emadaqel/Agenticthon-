<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class ArenaRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_page_returns_successful_response(): void
    {
        $this->get('/')->assertOk();
    }

    public function test_duels_page_returns_successful_response(): void
    {
        DB::table('scenarios')->insert($this->scenarioPayload());

        $this->get('/duels')->assertOk();
    }

    public function test_stats_endpoint_returns_empty_dashboard_shape(): void
    {
        $this->getJson('/api/stats')
            ->assertOk()
            ->assertJsonStructure([
                'total_duels',
                'total_turns',
                'overall_attack_success',
                'overall_defense_effectiveness',
                'total_red_wins',
                'total_blue_wins',
                'total_draws',
                'technique_breakdown',
                'owasp_distribution',
                'policy_comparison',
                'recent_duels',
            ]);
    }

    public function test_history_endpoint_returns_duel_list(): void
    {
        $scenarioId = (string) Str::uuid();
        DB::table('scenarios')->insert($this->scenarioPayload(['id' => $scenarioId]));

        DB::table('duel_summaries')->insert([
            'duel_id' => '11111111-1111-1111-1111-111111111111',
            'scenario_id' => $scenarioId,
            'target_model' => 'llama3-8b-8192',
            'policy_profile' => 'strict',
            'total_turns' => 1,
            'red_team_wins' => 0,
            'blue_team_wins' => 1,
            'draws' => 0,
            'false_positives' => 0,
            'attack_success_rate' => 0.0,
            'defense_effectiveness' => 1.0,
            'owasp_categories' => json_encode(['LLM01']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->getJson('/duels/history/all')
            ->assertOk()
            ->assertJsonPath('duels.0.duel_id', '11111111-1111-1111-1111-111111111111');
    }

    private function scenarioPayload(array $overrides = []): array
    {
        return array_merge([
            'id' => (string) Str::uuid(),
            'category' => 'jailbreak',
            'description' => 'Test scenario',
            'base_prompt' => 'You are a safe assistant.',
            'metadata' => json_encode(['severity' => 'LOW']),
            'attack_patterns' => json_encode(['direct_jailbreak']),
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides);
    }
}
