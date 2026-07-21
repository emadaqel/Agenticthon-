<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromptCase extends Model
{
    use HasUuids;

    protected $fillable = ['prompt_corpus_id', 'external_id', 'category', 'prompt', 'expected_policy', 'metadata'];

    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }

    public function corpus(): BelongsTo
    {
        return $this->belongsTo(PromptCorpus::class, 'prompt_corpus_id');
    }
}
