<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Integration extends Model
{
    use HasUuids, TenantScoped;

    protected $fillable = [
        'tenant_id', 'channel', 'provider', 'config_encrypted',
        'status', 'webhook_secret', 'last_sync_at',
    ];

    protected $casts = [
        'last_sync_at' => 'datetime',
    ];

    protected $hidden = ['config_encrypted'];
}
