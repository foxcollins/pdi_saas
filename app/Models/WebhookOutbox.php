<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class WebhookOutbox extends Model
{
    use HasUuids, TenantScoped;

    protected $table = 'webhook_outbox';

    protected $fillable = [
        'tenant_id', 'event', 'payload', 'status', 'attempts', 'next_attempt_at', 'response_code',
    ];

    protected $casts = [
        'payload' => 'array',
        'attempts' => 'integer',
        'next_attempt_at' => 'datetime',
    ];
}
