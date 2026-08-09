<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    use HasUuids, TenantScoped;

    protected $fillable = [
        'tenant_id', 'name', 'email', 'phone', 'whatsapp_id', 'instagram_username',
        'tags', 'lifecycle', 'consent_status', 'memory_summary', 'last_activity_at',
    ];

    protected $casts = [
        'tags' => 'array',
        'memory_summary' => 'array',
        'last_activity_at' => 'datetime',
    ];

    public function conversations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Conversation::class);
    }
}
