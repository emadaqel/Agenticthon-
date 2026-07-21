<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttackSurfaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_maps_untrusted_input_to_sensitive_mcp_actions_and_recommends_controls(): void
    {
        $response = $this->postJson('/api/attack-surfaces/analyze', [
            'name' => 'Support agent',
            'components' => [
                ['id' => 'input', 'name' => 'Customer message', 'type' => 'input', 'trust' => 'untrusted'],
                ['id' => 'model', 'name' => 'Support model', 'type' => 'model', 'trust' => 'trusted'],
                ['id' => 'mcp', 'name' => 'CRM MCP tool', 'type' => 'tool', 'permissions' => ['contacts:read', 'contacts:write']],
                ['id' => 'crm', 'name' => 'Customer database', 'type' => 'data', 'sensitivity' => 'sensitive'],
            ],
            'connections' => [
                ['from' => 'input', 'to' => 'model', 'sanitized' => false],
                ['from' => 'model', 'to' => 'mcp', 'approval_required' => false],
                ['from' => 'mcp', 'to' => 'crm', 'approval_required' => false],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('assessment.attack_paths.0.severity', 'CRITICAL')
            ->assertJsonPath('assessment.attack_paths.0.labels.3', 'Customer database')
            ->assertJsonPath('assessment.recommendations.0.control', 'human_approval');
        $this->assertGreaterThanOrEqual(0.75, $response->json('assessment.risk_score'));
    }

    public function test_it_rejects_edges_that_reference_unknown_components(): void
    {
        $this->postJson('/api/attack-surfaces/analyze', [
            'name' => 'Broken graph',
            'components' => [
                ['id' => 'input', 'name' => 'Input', 'type' => 'input', 'trust' => 'untrusted'],
                ['id' => 'model', 'name' => 'Model', 'type' => 'model'],
            ],
            'connections' => [['from' => 'input', 'to' => 'missing']],
        ])->assertUnprocessable()->assertJsonValidationErrors('connections.0');
    }
}
