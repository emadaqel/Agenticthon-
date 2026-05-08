<?php

namespace App\Http\Controllers;

use App\Models\DuelSummary;
use App\Models\DuelTurn;
use App\Models\Scenario;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function html(string $duelId)
    {
        $summary  = DuelSummary::where('duel_id', $duelId)->firstOrFail();
        $turns    = DuelTurn::where('duel_id', $duelId)->orderBy('turn')->get();
        $scenario = Scenario::find($summary->scenario_id);

        $remediations = $this->buildRemediations($summary, $turns);

        return view('report.html', compact('summary', 'turns', 'scenario', 'remediations'));
    }

    private function buildRemediations(DuelSummary $summary, $turns): array
    {
        $recs = [];

        if ($summary->attack_success_rate > 0.5) {
            $recs[] = [
                'priority' => 'CRITICAL',
                'title'    => 'High Attack Success Rate Detected',
                'detail'   => 'More than 50% of adversarial prompts bypassed defenses. Increase guardrail sensitivity and review policy thresholds immediately.',
                'controls' => ['Enable strict policy profile', 'Add input scanning', 'Review model system prompt'],
            ];
        }

        if ($summary->false_positives > 0) {
            $recs[] = [
                'priority' => 'MEDIUM',
                'title'    => 'False Positives Observed',
                'detail'   => "Defense controls blocked legitimate-looking inputs {$summary->false_positives} time(s). Tune scanner thresholds to reduce over-blocking.",
                'controls' => ['Calibrate PromptInjection scanner threshold', 'Review BanTopics list', 'Test with benign workload'],
            ];
        }

        $techniques = $turns->groupBy('attacker_technique');
        foreach ($techniques as $technique => $techniqueturns) {
            $wins = $techniqueturns->where('judge_outcome', 'red_team_win')->count();
            if ($wins > 0) {
                $recs[] = [
                    'priority' => 'HIGH',
                    'title'    => "Technique '{$technique}' Bypassed Defenses",
                    'detail'   => "The {$technique} technique succeeded {$wins} time(s). Add specific detection rules for this attack pattern.",
                    'controls' => ["Add {$technique} to block list", "Test guardrail coverage for this technique", "Review system prompt for vulnerabilities"],
                ];
            }
        }

        if (empty($recs)) {
            $recs[] = [
                'priority' => 'LOW',
                'title'    => 'Defense Posture Is Strong',
                'detail'   => 'All adversarial prompts were blocked or contained. Schedule next assessment in 30 days to validate ongoing resilience.',
                'controls' => ['Maintain current policy profile', 'Run monthly red-team assessments', 'Monitor guardrail service uptime'],
            ];
        }

        return $recs;
    }
}
