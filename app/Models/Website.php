<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Website extends Model
{
    use HasUuids, TenantScoped;

    protected $fillable = [
        'tenant_id', 'name', 'template', 'theme', 'pages', 'status', 'published_at',
    ];

    protected $casts = [
        'theme' => 'array',
        'pages' => 'array',
        'published_at' => 'datetime',
    ];

    public function getThemeAttribute($value): array
    {
        if (is_string($value)) {
            $value = json_decode($value, true) ?: [];
        }

        return array_merge(config('site.default_theme'), $value ?: []);
    }

    public function isLive(): bool
    {
        return $this->status === 'live';
    }
}
