<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromptCaseResult extends Model
{
    protected $fillable = [
        'prompt_corpus_run_id', 'prompt_case_id', 'passed', 'outcome', 'defender_verdict',
        'risk_score_input', 'risk_score_output', 'model_response', 'trace',
    ];

    protected $casts = ['passed' => 'boolean', 'trace' => 'array'];

    public function run(): BelongsTo
    {
        return $this->belongsTo(PromptCorpusRun::class, 'prompt_corpus_run_id');
    }

    public function promptCase(): BelongsTo
    {
        return $this->belongsTo(PromptCase::class);
    }
}
