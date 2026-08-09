<?php

namespace App\Http\Controllers;

use App\Models\AnalyticsEvent;
use App\Models\Tenant;
use App\Services\Site\DomainResolver;
use App\Services\Site\SiteRenderer;
use App\Support\TenantContext;
use Illuminate\Http\Request;

class PublicSiteController extends Controller
{
    public function show(Request $request, ?string $slug = null)
    {
        $tenant = $this->resolveTenant($slug);

        if (! $tenant) {
            abort(404);
        }

        TenantContext::set($tenant->id);

        $data = app(SiteRenderer::class)->render($tenant, $slug);

        if (! $data) {
            abort(404);
        }

        AnalyticsEvent::create(['kind' => 'page_view', 'context' => ['slug' => $tenant->slug]]);

        return inertia('PublicSite', $data);
    }

    private function resolveTenant(?string $slug): ?Tenant
    {
        if ($slug) {
            return Tenant::query()->where('slug', $slug)->where('status', '!=', 'suspended')->first();
        }

        return app(DomainResolver::class)->resolve(request()->getHost());
    }
}
