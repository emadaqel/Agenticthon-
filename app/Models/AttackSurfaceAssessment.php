<?php

namespace App\Models;

use App\Models\Concerns\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Model;

class AttackSurfaceAssessment extends Model
{
    use HasUuidPrimaryKey;

    protected $fillable = ['name', 'components', 'connections', 'attack_paths', 'recommendations', 'risk_score', 'status'];

    protected $casts = [
        'components' => 'array',
        'connections' => 'array',
        'attack_paths' => 'array',
        'recommendations' => 'array',
        'risk_score' => 'float',
    ];
}
