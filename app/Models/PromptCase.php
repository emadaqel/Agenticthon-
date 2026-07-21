<?php

namespace App\Models;

use App\Models\Concerns\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromptCase extends Model
{
    use HasUuidPrimaryKey;

    protected $fillable = ['prompt_corpus_id', 'external_id', 'category', 'prompt', 'expected_policy', 'metadata'];

    protected $casts = ['metadata' => 'array'];

    public function corpus(): BelongsTo
    {
        return $this->belongsTo(PromptCorpus::class, 'prompt_corpus_id');
    }
}
