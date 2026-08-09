<?php

use App\Models\Tenant;
use App\Services\Ai\AiManager;
use App\Support\TenantContext;

if (! function_exists('tenant')) {
    function tenant(): ?Tenant
    {
        return TenantContext::current();
    }
}

if (! function_exists('tenant_id')) {
    function tenant_id(): ?string
    {
        return TenantContext::id();
    }
}

if (! function_exists('ai')) {
    function ai(): AiManager
    {
        return app(AiManager::class);
    }
}
