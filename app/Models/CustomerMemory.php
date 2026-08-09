<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerMemory extends Model
{
    use HasUuids, TenantScoped;

    protected $fillable = [
        'tenant_id', 'contact_id', 'kind', 'content', 'window_start', 'window_end', 'policy',
    ];

    protected $casts = [
        'content' => 'array',
        'window_start' => 'datetime',
        'window_end' => 'datetime',
    ];

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }
}
