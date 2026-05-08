<?php

namespace App\Http\Controllers;

use App\Models\Scenario;
use App\Models\DuelTurn;
use App\Models\DuelSummary;
use App\AI\Agents\AttackerAgent;
use App\AI\Agents\DefenderAgent;
use App\AI\Agents\PolicyJudgeAgent;
use App\Services\ModelGateway;
use App\Services\NeMoGuardrailsService;
use App\Services\LlmGuardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class DuelController extends Controller
{
    public function __construct(
        protected AttackerAgent         $attacker,
        protected DefenderAgent         $defender,
        protected PolicyJudgeAgent      $judge,
        protected ModelGateway          $gateway,
        protected NeMoGuardrailsService $nemo,
        protected LlmGuardService       $llmGuard,
    ) {}

    // ─── GET /duels ────────────────────────────────────────────────────────────
    public function index()
    {
        $scenarios = Scenario::all();
        return view('duels.index', compact('scenarios'));
    }

    // ─── POST /duels/{scenario}/run ────────────────────────────────────────────
    public function run(Request $request, Scenario $scenario)
    {
        $maxTurns      = (int) $request->input('max_turns', 3);
        $policyProfile = $request->input('policy_profile', 'strict');
        $targetModel   = $request->input('target_model', 'llama3-8b-8192');
        $provider      = $request->input('provider', 'groq');

        $duelId  = (string) Str::uuid();
        $history = [];
        $turns   = [];

        // Cache live state for the /status endpoint
        Cache::put("duel:{$duelId}:status", ['status' => 'running', 'turns' => []], 600);

        for ($i = 1; $i <= $maxTurns; $i++) {

            // 1 ── Attacker generates adversarial prompt
            $attack = $this->attacker->generate($scenario, $history);
            $adversarialPrompt = $attack['prompt'] ?? '';

            // 2 ── Guardrail: check input
            $nemoInputResult  = $this->nemo->checkInput($adversarialPrompt);
            $guardInputResult = $this->llmGuard->scanInput($adversarialPrompt);

            // If input blocked by NeMo → blue win, skip to next turn
            if ($nemoInputResult['blocked'] ?? false) {
                $turnRecord = $this->buildTurnRecord($duelId, $scenario->id, $i, $attack, $guardInputResult, $nemoInputResult, '—BLOCKED BEFORE MODEL—', ['risk_score' => 1.0], ['blocked' => true], 'BLOCK', 0, $policyProfile);
                $judgeResult = ['outcome' => 'blue_team_win', 'owasp_category' => 'LLM01', 'reasoning' => 'Blocked at input by NeMo.'];
                $turnRecord = array_merge($turnRecord, $judgeResult);
                $this->persistTurn($turnRecord);
                $turns[]   = $turnRecord;
                $history[] = $turnRecord;
                $this->pushLiveStatus($duelId, $turns);
                continue;
            }

            // 3 ── Call target model
            $modelResult = $this->gateway->call($adversarialPrompt, $scenario->base_prompt, $targetModel, $provider);

            // 4 ── Guardrail: check output
            $nemoOutputResult  = $this->nemo->checkOutput($modelResult['response']);
            $guardOutputResult = $this->llmGuard->scanOutput($modelResult['response']);

            // 5 ── Defender evaluates
            $defenseResult = $this->defender->evaluate(
                $adversarialPrompt,
                $guardInputResult,
                $modelResult['response'],
                $guardOutputResult,
                $policyProfile
            );

            // 6 ── PolicyJudge scores the turn
            $judgeInput  = array_merge($attack, ['model_response' => $modelResult['response'], 'defender_verdict' => $defenseResult['verdict'] ?? 'BLOCK']);
            $judgeResult = $this->judge->scoreTurn($judgeInput);

            // 7 ── Build full turn record & persist
            $turnRecord = $this->buildTurnRecord(
                $duelId,
                $scenario->id,
                $i,
                $attack,
                $guardInputResult,
                $nemoInputResult,
                $modelResult['response'],
                $guardOutputResult,
                $nemoOutputResult,
                $defenseResult['verdict'] ?? 'BLOCK',
                $modelResult['latency_ms'] ?? 0,
                $policyProfile,
            );

            $turnRecord['judge_outcome']     = $judgeResult['outcome']        ?? 'draw';
            $turnRecord['owasp_category']    = $judgeResult['owasp_category'] ?? 'LLM01';
            $turnRecord['judge_reasoning']   = $judgeResult['reasoning']      ?? '';
            $turnRecord['defender_reasoning']= $defenseResult['reasoning']    ?? '';
            $turnRecord['modified_response'] = $defenseResult['modified_response'] ?? null;
            $turnRecord['tokens_used']       = ($modelResult['tokens_input'] ?? 0) + ($modelResult['tokens_output'] ?? 0);

            $this->persistTurn($turnRecord);
            $turns[]   = $turnRecord;
            $history[] = $turnRecord;
            $this->pushLiveStatus($duelId, $turns);

            // If attacker got through → red win; duel ends early
            if ($turnRecord['judge_outcome'] === 'red_team_win') {
                break;
            }
        }

        // 8 ── Final duel summary
        $summary = $this->judge->summarize($duelId, $scenario->category, $turns);

        DuelSummary::create([
            'duel_id'               => $duelId,
            'scenario_id'           => $scenario->id,
            'target_model'          => $targetModel,
            'policy_profile'        => $policyProfile,
            'total_turns'           => $summary['total_turns'],
            'red_team_wins'         => $summary['red_team_wins'],
            'blue_team_wins'        => $summary['blue_team_wins'],
            'draws'                 => $summary['draws'],
            'false_positives'       => $summary['false_positives'],
            'attack_success_rate'   => $summary['attack_success_rate'],
            'defense_effectiveness' => $summary['defense_effectiveness'],
            'owasp_categories'      => $summary['owasp_categories_triggered'],
        ]);

        Cache::put("duel:{$duelId}:status", ['status' => 'complete', 'turns' => $turns, 'summary' => $summary], 600);

        return response()->json([
            'duel_id'  => $duelId,
            'scenario' => $scenario->category,
            'summary'  => $summary,
            'turns'    => $turns,
        ]);
    }

    // ─── GET /duels/{duel}/status ──────────────────────────────────────────────
    public function status(string $duelId)
    {
        $data = Cache::get("duel:{$duelId}:status", ['status' => 'not_found', 'turns' => []]);
        return response()->json($data);
    }

    // ─── GET /duels/{duel}/report ─────────────────────────────────────────────
    public function report(string $duelId)
    {
        $summary = DuelSummary::findOrFail($duelId);
        $turns   = DuelTurn::where('duel_id', $duelId)->orderBy('turn')->get();
        return response()->json(['summary' => $summary, 'turns' => $turns]);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function buildTurnRecord(
        string $duelId,
        string $scenarioId,
        int    $turn,
        array  $attack,
        array  $guardInputResult,
        array  $nemoInputResult,
        string $modelResponse,
        array  $guardOutputResult,
        array  $nemoOutputResult,
        string $verdict,
        int    $latencyMs,
        string $policyProfile,
    ): array {
        return [
            'duel_id'                => $duelId,
            'scenario_id'            => $scenarioId,
            'turn'                   => $turn,
            'attacker_technique'     => $attack['technique_used'] ?? 'unknown',
            'attacker_reasoning'     => $attack['reasoning']      ?? '',
            'adversarial_prompt'     => $attack['prompt']         ?? '',
            'guardrail_input_result' => array_merge($guardInputResult, $nemoInputResult),
            'model_response'         => $modelResponse,
            'guardrail_output_result'=> array_merge($guardOutputResult, $nemoOutputResult),
            'defender_verdict'       => $verdict,
            'risk_score_input'       => $guardInputResult['risk_score']  ?? 0.0,
            'risk_score_output'      => $guardOutputResult['risk_score'] ?? 0.0,
            'latency_ms'             => $latencyMs,
            'policy_profile'         => $policyProfile,
            'source'                 => 'web',
        ];
    }

    private function persistTurn(array $turnRecord): void
    {
        DuelTurn::create([
            'duel_id'                => $turnRecord['duel_id'],
            'scenario_id'            => $turnRecord['scenario_id'],
            'turn'                   => $turnRecord['turn'],
            'attacker_technique'     => $turnRecord['attacker_technique'],
            'adversarial_prompt'     => $turnRecord['adversarial_prompt'],
            'guardrail_input_result' => $turnRecord['guardrail_input_result'],
            'model_response'         => $turnRecord['model_response'],
            'guardrail_output_result'=> $turnRecord['guardrail_output_result'],
            'defender_verdict'       => $turnRecord['defender_verdict'],
            'judge_outcome'          => $turnRecord['judge_outcome'] ?? null,
            'owasp_category'         => $turnRecord['owasp_category'] ?? null,
            'risk_score_input'       => $turnRecord['risk_score_input'],
            'risk_score_output'      => $turnRecord['risk_score_output'],
            'latency_ms'             => $turnRecord['latency_ms'],
            'tokens_used'            => $turnRecord['tokens_used'] ?? 0,
            'source'                 => $turnRecord['source'] ?? 'web',
        ]);
    }

    private function pushLiveStatus(string $duelId, array $turns): void
    {
        $current = Cache::get("duel:{$duelId}:status", []);
        Cache::put("duel:{$duelId}:status", array_merge($current, ['turns' => $turns]), 600);
    }
}
