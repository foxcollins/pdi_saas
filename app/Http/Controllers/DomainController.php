<?php

namespace App\Http\Controllers;

use App\Jobs\VerifyDomainTxt;
use App\Models\Domain;
use App\Services\Site\DnsTxtVerifier;
use App\Services\Site\DomainResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DomainController extends Controller
{
    public function show()
    {
        $domains = $this->tenantDomains()
            ->orderByDesc('is_primary')
            ->get()
            ->map(fn ($d) => [
                'id' => $d->id,
                'host' => $d->host,
                'is_primary' => $d->is_primary,
                'status' => $d->status,
                'verified_at' => $d->verified_at?->toIso8601String(),
                'verification_token' => $d->verification_token,
                'record_name' => app(DnsTxtVerifier::class)->recordName($d->host),
                'last_checked_at' => $d->last_checked_at?->toIso8601String(),
            ]);

        return inertia('Domains', [
            'domains' => $domains,
            'platform_domain' => config('site.platform_domain'),
            'default_url' => url('/site/'.tenant()->slug),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'host' => ['required', 'string', 'max:255'],
        ]);

        $host = app(DomainResolver::class)->normalize($request->host);

        $exists = Domain::query()->where('host', $host)->exists();

        if ($exists) {
            return back()->with('success', 'Ese dominio ya está registrado en la plataforma.');
        }

        $domain = Domain::create([
            'tenant_id' => tenant()->id,
            'host' => $host,
            'status' => 'pending',
            'verification_token' => Str::random(config('site.domain_verification.token_bytes', 32) * 2),
        ]);

        VerifyDomainTxt::dispatch($domain);

        return back()->with('success', 'Dominio agregado. Configura el registro TXT indicado.');
    }

    public function verify(string $domainId)
    {
        $domain = $this->tenantDomains()->findOrFail($domainId);

        if ($domain->status === 'verified') {
            return back()->with('success', 'El dominio ya está verificado.');
        }

        VerifyDomainTxt::dispatch($domain);

        return back()->with('success', 'Verificación solicitada. Revisa el estado en unos instantes.');
    }

    public function makePrimary(string $domainId)
    {
        $domain = $this->tenantDomains()->findOrFail($domainId);

        $this->tenantDomains()->update(['is_primary' => false]);
        $domain->update(['is_primary' => true]);

        return back()->with('success', 'Dominio principal actualizado.');
    }

    public function destroy(string $domainId)
    {
        $this->tenantDomains()->findOrFail($domainId)->delete();

        return back()->with('success', 'Dominio eliminado.');
    }

    private function tenantDomains()
    {
        return Domain::query()->where('tenant_id', tenant()->id);
    }
}
