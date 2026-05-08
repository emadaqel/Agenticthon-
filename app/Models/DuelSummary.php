<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DuelSummary extends Model
{
    protected $primaryKey = 'duel_id';
    public    $incrementing = false;
    protected $keyType      = 'string';

    protected $fillable = [
        'duel_id',
        'scenario_id',
        'target_model',
        'policy_profile',
        'total_turns',
        'red_team_wins',
        'blue_team_wins',
        'draws',
        'false_positives',
        'attack_success_rate',
        'defense_effectiveness',
        'vulnerabilities_found',
        'owasp_categories',
    ];

    protected $casts = [
        'vulnerabilities_found' => 'array',
        'owasp_categories'      => 'array',
    ];
}
