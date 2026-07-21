<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_security_workspace_renders_the_integrated_workflow(): void
    {
        $this->get('/security')
            ->assertOk()
            ->assertSee('Trace attacks. Prove controls. Ship safely.')
            ->assertSee('/api/security/demo', false)
            ->assertSee('/api/corpora', false)
            ->assertSee('/api/attack-surfaces/analyze', false);
    }

    public function test_workspace_demo_and_sample_attack_analysis_are_operational(): void
    {
        $this->getJson('/api/security/demo')
            ->assertOk()
            ->assertJsonPath('demo.before.failed', 2)
            ->assertJsonPath('demo.after.failed', 0);

        $this->postJson('/api/attack-surfaces/analyze', [
            'name' => 'Workspace sample',
            'components' => [
                ['id' => 'input', 'name' => 'User input', 'type' => 'input', 'trust' => 'untrusted', 'sensitivity' => 'public'],
                ['id' => 'agent', 'name' => 'Agent', 'type' => 'model', 'trust' => 'trusted', 'sensitivity' => 'internal'],
                ['id' => 'vault', 'name' => 'Vault', 'type' => 'data', 'trust' => 'trusted', 'sensitivity' => 'sensitive'],
            ],
            'connections' => [
                ['from' => 'input', 'to' => 'agent', 'sanitized' => false],
                ['from' => 'agent', 'to' => 'vault', 'approval_required' => false],
            ],
        ])->assertCreated()->assertJsonPath('assessment.attack_paths.0.severity', 'MEDIUM');
    }
}
