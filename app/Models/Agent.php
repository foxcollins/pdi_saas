<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Agent extends Model
{
    use HasUuids, TenantScoped;

    protected $fillable = [
        'tenant_id', 'slug', 'name', 'instructions', 'tools',
        'model_profile_id', 'is_active', 'guardrails',
    ];

    protected $casts = [
        'tools' => 'array',
        'is_active' => 'boolean',
        'guardrails' => 'array',
    ];
}
