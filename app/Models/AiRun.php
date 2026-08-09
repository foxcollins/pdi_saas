<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AiRun extends Model
{
    use HasUuids, TenantScoped;

    protected $fillable = [
        'tenant_id', 'trigger', 'model_profile_id', 'tokens_in',
        'tokens_out', 'cost_usd', 'latency_ms', 'cached',
    ];

    protected $casts = [
        'tokens_in' => 'integer',
        'tokens_out' => 'integer',
        'cost_usd' => 'decimal:8',
        'latency_ms' => 'integer',
        'cached' => 'boolean',
    ];
}
