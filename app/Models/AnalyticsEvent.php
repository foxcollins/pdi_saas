<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AnalyticsEvent extends Model
{
    use HasUuids, TenantScoped;

    protected $fillable = ['tenant_id', 'kind', 'context'];

    protected $casts = ['context' => 'array'];
}
