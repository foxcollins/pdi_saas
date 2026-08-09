<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class BusinessProfile extends Model
{
    use HasUuids, TenantScoped;

    protected $fillable = [
        'tenant_id', 'name', 'tagline', 'description', 'logo_url', 'industry',
        'services', 'products', 'branches', 'schedule', 'contact',
        'social', 'faqs', 'team', 'certifications',
    ];

    protected $casts = [
        'services' => 'array',
        'products' => 'array',
        'branches' => 'array',
        'schedule' => 'array',
        'contact' => 'array',
        'social' => 'array',
        'faqs' => 'array',
        'team' => 'array',
        'certifications' => 'array',
    ];
}
