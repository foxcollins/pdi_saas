<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lead extends Model
{
    use HasUuids, TenantScoped;

    protected $fillable = [
        'tenant_id', 'contact_id', 'source_channel', 'intent',
        'lead_score', 'status', 'next_action', 'assigned_user_id',
    ];

    protected $casts = [
        'lead_score' => 'integer',
    ];

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }
}
