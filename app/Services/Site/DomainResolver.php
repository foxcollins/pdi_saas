<?php

namespace App\Services\Site;

use App\Models\Domain;
use App\Models\Tenant;
use Illuminate\Support\Str;

class DomainResolver
{
    public function resolve(string $host): ?Tenant
    {
        $host = $this->normalize($host);

        if ($host === '' || $host === 'localhost') {
            return null;
        }

        foreach ([$host, $this->stripWww($host)] as $candidate) {
            $domain = Domain::query()
                ->whereIn('host', array_unique([$candidate, 'www.'.$candidate]))
                ->where('status', 'verified')
                ->first();

            if ($domain) {
                return $domain->tenant;
            }
        }

        $platformDomain = strtolower(trim((string) config('site.platform_domain')));

        if ($platformDomain !== '' && str_ends_with($host, '.'.$platformDomain)) {
            $slug = Str::before($host, '.'.$platformDomain);

            return Tenant::query()->where('slug', $slug)->first();
        }

        return null;
    }

    public function normalize(string $host): string
    {
        $host = strtolower(trim($host));

        $host = preg_replace('#^https?://#', '', $host);
        $host = rtrim($host, '/');

        $parts = explode(':', $host);

        return $parts[0];
    }

    private function stripWww(string $host): string
    {
        return str_starts_with($host, 'www.') ? substr($host, 4) : $host;
    }
}
