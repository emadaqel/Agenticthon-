<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RemediationProposal extends Model
{
    use HasUuids;

    protected $fillable = [
        'security_finding_id', 'status', 'advisor_provider', 'advisor_model', 'generation_mode',
        'summary', 'proposed_changes', 'regression_tests', 'reviewer_note', 'reviewed_at',
    ];

    protected function casts(): array
    {
        return ['proposed_changes' => 'array', 'regression_tests' => 'array', 'reviewed_at' => 'datetime'];
    }

    public function finding(): BelongsTo
    {
        return $this->belongsTo(SecurityFinding::class, 'security_finding_id');
    }
}
