<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Scenario extends Model
{
    use HasUuids;

    protected $fillable = [
        'category',
        'description',
        'base_prompt',
        'metadata',
        'attack_patterns',
    ];

    protected $casts = [
        'metadata' => 'array',
        'attack_patterns' => 'array',
    ];
}
