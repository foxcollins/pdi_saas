<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KnowledgeChunk extends Model
{
    use HasUuids, TenantScoped;

    protected $fillable = [
        'tenant_id', 'document_id', 'chunk_index', 'content',
        'token_count', 'source_ref', 'embedding',
    ];

    protected $casts = [
        'chunk_index' => 'integer',
        'token_count' => 'integer',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(KnowledgeDocument::class, 'document_id');
    }

    public function setEmbeddingAttribute(array|string $value): void
    {
        $this->attributes['embedding'] = is_array($value) ? '['.implode(',', $value).']' : $value;
    }
}
