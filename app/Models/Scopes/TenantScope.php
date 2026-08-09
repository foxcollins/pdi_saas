<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $id = tenant_id();

        if ($id) {
            $builder->where($model->getTable().'.tenant_id', $id);
        } else {
            $builder->whereRaw('1 = 0');
        }
    }
}
