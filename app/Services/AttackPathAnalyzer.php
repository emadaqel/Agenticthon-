<?php

namespace App\Services;

use App\Models\AttackSurfaceAssessment;
use Illuminate\Validation\ValidationException;

class AttackPathAnalyzer
{
    public function analyze(string $name, array $components, array $connections): AttackSurfaceAssessment
    {
        $nodes = collect($components)->keyBy('id');
        $this->validateGraph($nodes->all(), $connections);

        $adjacency = [];
        foreach ($connections as $edge) {
            $adjacency[$edge['from']][] = $edge;
        }

        $sources = $nodes->filter(fn (array $node) => ($node['trust'] ?? '') === 'untrusted')->keys();
        $paths = [];
        foreach ($sources as $source) {
            $this->walk($source, $nodes->all(), $adjacency, [$source], [], $paths);
        }

        $riskScore = $paths === [] ? 0.0 : max(array_column($paths, 'risk_score'));
        $recommendations = $this->recommendations($paths);

        return AttackSurfaceAssessment::create([
            'name' => $name,
            'components' => array_values($components),
            'connections' => array_values($connections),
            'attack_paths' => $paths,
            'recommendations' => $recommendations,
            'risk_score' => $riskScore,
            'status' => 'complete',
        ]);
    }

    private function walk(string $current, array $nodes, array $adjacency, array $nodePath, array $edgePath, array &$paths): void
    {
        if (count($nodePath) > 8) {
            return;
        }

        $node = $nodes[$current];
        if (count($nodePath) > 1 && ($node['sensitivity'] ?? '') === 'sensitive') {
            $paths[] = $this->scorePath($nodePath, $edgePath, $nodes);
            return;
        }

        foreach ($adjacency[$current] ?? [] as $edge) {
            if (in_array($edge['to'], $nodePath, true)) {
                continue;
            }
            $this->walk($edge['to'], $nodes, $adjacency, [...$nodePath, $edge['to']], [...$edgePath, $edge], $paths);
        }
    }

    private function scorePath(array $nodePath, array $edgePath, array $nodes): array
    {
        $pathNodes = array_map(fn (string $id) => $nodes[$id], $nodePath);
        $hasModel = collect($pathNodes)->contains(fn (array $node) => $node['type'] === 'model');
        $hasTool = collect($pathNodes)->contains(fn (array $node) => $node['type'] === 'tool');
        $hasApproval = collect($edgePath)->contains(fn (array $edge) => (bool) ($edge['approval_required'] ?? false));
        $hasSanitizer = collect($edgePath)->contains(fn (array $edge) => (bool) ($edge['sanitized'] ?? false));
        $permissions = collect($pathNodes)->flatMap(fn (array $node) => $node['permissions'] ?? [])->unique()->values()->all();

        $score = 0.35;
        $score += $hasModel ? 0.15 : 0;
        $score += $hasTool ? 0.2 : 0;
        $score += $permissions !== [] ? 0.15 : 0;
        $score -= $hasApproval ? 0.25 : 0;
        $score -= $hasSanitizer ? 0.15 : 0;
        $score = round(max(0, min(1, $score)), 2);

        return [
            'nodes' => $nodePath,
            'labels' => array_column($pathNodes, 'name'),
            'risk_score' => $score,
            'severity' => $score >= 0.75 ? 'CRITICAL' : ($score >= 0.55 ? 'HIGH' : 'MEDIUM'),
            'permissions' => $permissions,
            'controls' => ['approval_required' => $hasApproval, 'sanitized' => $hasSanitizer],
            'reason' => 'Untrusted input can reach a sensitive component through the agent graph.',
        ];
    }

    private function recommendations(array $paths): array
    {
        $recommendations = [];
        if (collect($paths)->contains(fn (array $path) => ! $path['controls']['approval_required'])) {
            $recommendations[] = ['control' => 'human_approval', 'action' => 'Require explicit approval before any sensitive tool or data action.'];
        }
        if (collect($paths)->contains(fn (array $path) => ! $path['controls']['sanitized'])) {
            $recommendations[] = ['control' => 'trust_boundary', 'action' => 'Validate and label untrusted content before it enters model or tool context.'];
        }
        if (collect($paths)->contains(fn (array $path) => $path['permissions'] !== [])) {
            $recommendations[] = ['control' => 'least_privilege', 'action' => 'Replace broad tool permissions with task-scoped read/write capabilities.'];
        }

        return $recommendations;
    }

    private function validateGraph(array $nodes, array $connections): void
    {
        foreach ($connections as $index => $edge) {
            if (! isset($nodes[$edge['from']], $nodes[$edge['to']])) {
                throw ValidationException::withMessages(["connections.{$index}" => 'Both edge endpoints must reference component IDs.']);
            }
        }
    }
}
