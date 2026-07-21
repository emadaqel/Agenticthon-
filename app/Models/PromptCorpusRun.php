<?php

namespace App\Models;

use App\Models\Concerns\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PromptCorpusRun extends Model
{
    use HasUuidPrimaryKey;

    protected $fillable = [
        'prompt_corpus_id', 'scenario_id', 'corpus_hash', 'target_model', 'provider',
        'policy_profile', 'status', 'total_cases', 'passed_cases', 'failed_cases', 'metadata',
    ];

    protected $casts = ['metadata' => 'array'];

    public function corpus(): BelongsTo
    {
        return $this->belongsTo(PromptCorpus::class, 'prompt_corpus_id');
    }

    public function scenario(): BelongsTo
    {
        return $this->belongsTo(Scenario::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(PromptCaseResult::class);
    }

    public function findings(): HasMany
    {
        return $this->hasMany(SecurityFinding::class);
    }
}
