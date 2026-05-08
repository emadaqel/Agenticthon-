<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DuelTurn extends Model
{
    protected $fillable = [
        'duel_id',
        'scenario_id',
        'turn',
        'attacker_technique',
        'adversarial_prompt',
        'guardrail_input_result',
        'model_response',
        'guardrail_output_result',
        'defender_verdict',
        'judge_outcome',
        'owasp_category',
        'risk_score_input',
        'risk_score_output',
        'latency_ms',
        'tokens_used',
        'source',
    ];

    protected $casts = [
        'guardrail_input_result'  => 'array',
        'guardrail_output_result' => 'array',
    ];
}
