<?php

namespace App\Services;

use App\Models\DuelSummary;
use App\Models\DuelTurn;

class EvaluationService
{
    /**
     * Get cross-scenario analytics for the dashboard.
     */
    public function getDashboardStats(): array
    {
        $summaries = DuelSummary::all();
        $turns     = DuelTurn::all();

        if ($summaries->isEmpty()) {
            return $this->emptyStats();
        }

        return [
            'total_duels'              => $summaries->count(),
            'total_turns'              => $turns->count(),
            'overall_attack_success'   => round($summaries->avg('attack_success_rate') * 100, 1),
            'overall_defense_effectiveness' => round($summaries->avg('defense_effectiveness') * 100, 1),
            'total_red_wins'           => $summaries->sum('red_team_wins'),
            'total_blue_wins'          => $summaries->sum('blue_team_wins'),
            'total_draws'              => $summaries->sum('draws'),
            'technique_breakdown'      => $this->getTechniqueBreakdown($turns),
            'owasp_distribution'       => $this->getOwaspDistribution($turns),
            'policy_comparison'        => $this->getPolicyComparison($summaries),
            'model_comparison'         => $this->getModelComparison($summaries),
            'category_performance'     => $this->getCategoryPerformance($summaries),
            'recent_duels'             => $this->getRecentDuels($summaries),
        ];
    }

    /**
     * Technique effectiveness breakdown.
     */
    protected function getTechniqueBreakdown($turns): array
    {
        $techniques = [];

        foreach ($turns as $turn) {
            $technique = $turn->attacker_technique ?? 'unknown';
            if (!isset($techniques[$technique])) {
                $techniques[$technique] = ['attempts' => 0, 'successes' => 0, 'blocks' => 0];
            }
            $techniques[$technique]['attempts']++;

            if ($turn->judge_outcome === 'red_team_win') {
                $techniques[$technique]['successes']++;
            } elseif ($turn->judge_outcome === 'blue_team_win') {
                $techniques[$technique]['blocks']++;
            }
        }

        // Calculate success rate for each technique
        foreach ($techniques as &$stats) {
            $stats['success_rate'] = $stats['attempts'] > 0
                ? round(($stats['successes'] / $stats['attempts']) * 100, 1)
                : 0;
        }

        // Sort by success rate descending
        uasort($techniques, fn($a, $b) => $b['success_rate'] <=> $a['success_rate']);

        return $techniques;
    }

    /**
     * OWASP category distribution across all duels.
     */
    protected function getOwaspDistribution($turns): array
    {
        $categories = [];

        foreach ($turns as $turn) {
            $cat = $turn->owasp_category ?? 'Unknown';
            $categories[$cat] = ($categories[$cat] ?? 0) + 1;
        }

        arsort($categories);
        return $categories;
    }

    /**
     * Compare defense effectiveness across policy profiles.
     */
    protected function getPolicyComparison($summaries): array
    {
        $policies = [];

        foreach ($summaries->groupBy('policy_profile') as $profile => $group) {
            $policies[$profile] = [
                'duels'                  => $group->count(),
                'avg_attack_success'     => round($group->avg('attack_success_rate') * 100, 1),
                'avg_defense_effectiveness' => round($group->avg('defense_effectiveness') * 100, 1),
                'total_red_wins'         => $group->sum('red_team_wins'),
                'total_blue_wins'        => $group->sum('blue_team_wins'),
            ];
        }

        return $policies;
    }

    /**
     * Compare model vulnerability across different target models.
     */
    protected function getModelComparison($summaries): array
    {
        $models = [];

        foreach ($summaries->groupBy('target_model') as $model => $group) {
            $models[$model] = [
                'duels'              => $group->count(),
                'avg_attack_success' => round($group->avg('attack_success_rate') * 100, 1),
                'total_red_wins'     => $group->sum('red_team_wins'),
                'total_blue_wins'    => $group->sum('blue_team_wins'),
            ];
        }

        return $models;
    }

    /**
     * Performance breakdown by scenario category.
     */
    protected function getCategoryPerformance($summaries): array
    {
        $categories = [];
        $scenarioMap = \App\Models\Scenario::pluck('category', 'id')->toArray();

        foreach ($summaries as $summary) {
            $category = $scenarioMap[$summary->scenario_id] ?? 'unknown';
            if (!isset($categories[$category])) {
                $categories[$category] = ['duels' => 0, 'red_wins' => 0, 'blue_wins' => 0, 'draws' => 0];
            }
            $categories[$category]['duels']++;
            $categories[$category]['red_wins']  += $summary->red_team_wins;
            $categories[$category]['blue_wins'] += $summary->blue_team_wins;
            $categories[$category]['draws']     += $summary->draws;
        }

        return $categories;
    }

    /**
     * Get recent duels for the dashboard feed.
     */
    protected function getRecentDuels($summaries): array
    {
        return $summaries->sortByDesc('created_at')
            ->take(10)
            ->map(fn($s) => [
                'duel_id'          => $s->duel_id,
                'target_model'     => $s->target_model,
                'policy_profile'   => $s->policy_profile,
                'total_turns'      => $s->total_turns,
                'red_wins'         => $s->red_team_wins,
                'blue_wins'        => $s->blue_team_wins,
                'attack_success'   => round($s->attack_success_rate * 100, 1) . '%',
                'created_at'       => $s->created_at?->diffForHumans(),
            ])
            ->values()
            ->toArray();
    }

    /**
     * Empty stats for when no duels have been run.
     */
    protected function emptyStats(): array
    {
        return [
            'total_duels'                    => 0,
            'total_turns'                    => 0,
            'overall_attack_success'         => 0,
            'overall_defense_effectiveness'  => 0,
            'total_red_wins'                 => 0,
            'total_blue_wins'                => 0,
            'total_draws'                    => 0,
            'technique_breakdown'            => [],
            'owasp_distribution'             => [],
            'policy_comparison'              => [],
            'model_comparison'               => [],
            'category_performance'           => [],
            'recent_duels'                   => [],
        ];
    }
}
