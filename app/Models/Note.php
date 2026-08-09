<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Note extends Model
{
    use HasUuids, TenantScoped;

    protected $fillable = ['tenant_id', 'contact_id', 'author_id', 'body'];

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }
}
