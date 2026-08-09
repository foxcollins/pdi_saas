<?php

namespace App\Models\Concerns;

use App\Models\Scopes\TenantScope;
use RuntimeException;

trait TenantScoped
{
    public static function bootTenantScoped(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function ($model) {
            if (blank($model->tenant_id)) {
                $model->tenant_id = tenant_id();
            }

            if (blank($model->tenant_id)) {
                throw new RuntimeException('Contexto de tenant no establecido al crear '.class_basename($model).'.');
            }
        });
    }

    public function tenant(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
