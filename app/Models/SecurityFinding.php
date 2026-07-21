<?php

namespace App\Models;

use App\Models\Concerns\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SecurityFinding extends Model
{
    use HasUuidPrimaryKey;

    protected $fillable = [
        'prompt_corpus_run_id', 'prompt_case_result_id', 'title', 'category', 'severity',
        'status', 'description', 'exploit_chain', 'evidence',
    ];

    protected $casts = ['exploit_chain' => 'array', 'evidence' => 'array'];

    public function run(): BelongsTo
    {
        return $this->belongsTo(PromptCorpusRun::class, 'prompt_corpus_run_id');
    }

    public function result(): BelongsTo
    {
        return $this->belongsTo(PromptCaseResult::class, 'prompt_case_result_id');
    }

    public function proposals(): HasMany
    {
        return $this->hasMany(RemediationProposal::class);
    }
}
