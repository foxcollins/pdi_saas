<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contact extends Model
{
    use HasUuids, TenantScoped;

    protected $fillable = [
        'tenant_id', 'name', 'email', 'phone', 'whatsapp_id', 'instagram_username',
        'tags', 'lifecycle', 'consent_status', 'memory_summary', 'last_activity_at', 'anonymized_at',
    ];

    protected $casts = [
        'tags' => 'array',
        'memory_summary' => 'array',
        'last_activity_at' => 'datetime',
        'anonymized_at' => 'datetime',
    ];

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(Note::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function memoryEntries(): HasMany
    {
        return $this->hasMany(CustomerMemory::class);
    }
}
