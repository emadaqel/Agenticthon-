<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AttackSurfaceAssessment extends Model
{
    use HasUuids;

    protected $fillable = ['name', 'components', 'connections', 'attack_paths', 'recommendations', 'risk_score', 'status'];

    protected function casts(): array
    {
        return [
            'components' => 'array',
            'connections' => 'array',
            'attack_paths' => 'array',
            'recommendations' => 'array',
            'risk_score' => 'float',
        ];
    }
}
