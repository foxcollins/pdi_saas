<?php

namespace App\Support;

use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

class TenantContext
{
    private static ?Tenant $current = null;

    public static function set(?string $tenantId): void
    {
        self::$current = $tenantId ? Tenant::query()->withoutGlobalScopes()->find($tenantId) : null;

        DB::statement("select set_config('app.tenant_id', ?, false)", [$tenantId ?? '']);
    }

    public static function current(): ?Tenant
    {
        return self::$current;
    }

    public static function id(): ?string
    {
        return self::$current?->id;
    }
}
