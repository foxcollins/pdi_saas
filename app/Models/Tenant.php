<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'slug',
        'industry',
        'country',
        'plan_id',
        'status',
        'settings',
    ];

    protected $casts = [
        'settings' => 'array',
    ];

    public function plan(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function website(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Website::class);
    }

    public function users(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    public function profile(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(BusinessProfile::class);
    }

    public function domains(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Domain::class);
    }

    public function agents(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Agent::class);
    }
}
