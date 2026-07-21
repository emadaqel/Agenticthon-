<?php

namespace App\Models;

use App\Models\Concerns\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Model;

class Scenario extends Model
{
    use HasUuidPrimaryKey;

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
