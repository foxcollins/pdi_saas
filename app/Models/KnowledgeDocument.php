<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KnowledgeDocument extends Model
{
    use HasUuids, TenantScoped;

    protected $fillable = [
        'tenant_id', 'source_id', 'filename', 'mime', 'storage_key',
        'status', 'chunk_count', 'embedding_model', 'embedding_dimensions', 'error',
    ];

    protected $casts = [
        'chunk_count' => 'integer',
        'embedding_dimensions' => 'integer',
    ];

    public function source(): BelongsTo
    {
        return $this->belongsTo(KnowledgeSource::class, 'source_id');
    }

    public function chunks(): HasMany
    {
        return $this->hasMany(KnowledgeChunk::class, 'document_id');
    }
}
