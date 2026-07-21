<?php

namespace App\Models;

use App\Models\Concerns\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PromptCorpus extends Model
{
    use HasUuidPrimaryKey;

    protected $table = 'prompt_corpora';

    protected $fillable = ['name', 'source_url', 'source_ref', 'source_sha', 'content_hash', 'last_synced_at', 'metadata'];

    protected $casts = ['metadata' => 'array', 'last_synced_at' => 'datetime'];

    public function cases(): HasMany
    {
        return $this->hasMany(PromptCase::class);
    }

    public function runs(): HasMany
    {
        return $this->hasMany(PromptCorpusRun::class);
    }
}
