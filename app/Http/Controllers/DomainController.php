<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Services\Site\DomainResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DomainController extends Controller
{
    public function show()
    {
        $domains = Domain::query()
            ->orderByDesc('is_primary')
            ->get()
            ->map(fn ($d) => [
                'id' => $d->id,
                'host' => $d->host,
                'is_primary' => $d->is_primary,
                'status' => $d->status,
                'verified_at' => $d->verified_at?->toIso8601String(),
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

        Domain::firstOrCreate(
            ['host' => $host],
            ['tenant_id' => tenant()->id, 'status' => 'pending']
        );

        return back()->with('success', 'Dominio agregado. Configura el registro DNS indicado.');
    }

    public function verify(Request $request, string $domainId)
    {
        $domain = Domain::findOrFail($domainId);

        $expected = 'pdi-verify='.Str::slug($domain->host);

        if ($request->input('token') === $expected) {
            $domain->update(['status' => 'verified', 'verified_at' => now()]);

            return back()->with('success', 'Dominio verificado.');
        }

        return back()->with('success', "Agrega el registro TXT con el valor: {$expected} y vuelve a intentar.");
    }

    public function makePrimary(Request $request, string $domainId)
    {
        $domain = Domain::findOrFail($domainId);

        Domain::query()->update(['is_primary' => false]);
        $domain->update(['is_primary' => true]);

        return back()->with('success', 'Dominio principal actualizado.');
    }

    public function destroy(Request $request, string $domainId)
    {
        Domain::findOrFail($domainId)->delete();

        return back()->with('success', 'Dominio eliminado.');
    }
}
