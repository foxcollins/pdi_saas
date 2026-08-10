<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ToolRun extends Model
{
    use HasUuids, TenantScoped;

    protected $fillable = [
        'tenant_id', 'agent_id', 'conversation_id', 'tool',
        'input', 'output', 'status', 'error', 'latency_ms',
    ];

    protected $casts = [
        'input' => 'array',
        'output' => 'array',
        'latency_ms' => 'integer',
    ];
}
