<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KnowledgeSource extends Model
{
    use HasUuids, TenantScoped;

    protected $fillable = ['tenant_id', 'type', 'title', 'status', 'meta'];

    protected $casts = ['meta' => 'array'];

    public function documents(): HasMany
    {
        return $this->hasMany(KnowledgeDocument::class, 'source_id');
    }
}
