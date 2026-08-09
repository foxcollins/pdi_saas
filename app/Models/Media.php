<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    use HasUuids, TenantScoped;

    protected $fillable = ['tenant_id', 'file_key', 'url', 'mime', 'size', 'alt'];
}
