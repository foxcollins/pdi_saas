<?php

namespace App\Http\Middleware;

use App\Support\TenantContext as TenantContextSupport;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TenantContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = null;

        if ($request->user()) {
            $tenant = $request->user()->tenants()->find($request->session()->get('current_tenant_id'))
                ?? $request->user()->tenants()->first();
        }

        TenantContextSupport::set($tenant?->id);

        return $next($request);
    }
}
