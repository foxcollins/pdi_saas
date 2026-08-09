<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasUuids;

    protected $fillable = ['name', 'slug', 'price_monthly', 'limits'];

    protected $casts = [
        'price_monthly' => 'decimal:2',
        'limits' => 'array',
    ];
}
