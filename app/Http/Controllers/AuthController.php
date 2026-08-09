<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\BusinessProfile;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Site\WebsiteBuilderService;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function showLogin()
    {
        return inertia('Auth/Login');
    }

    public function showRegister()
    {
        return inertia('Auth/Register');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Credenciales incorrectas.']);
        }

        $request->session()->regenerate();

        $tenant = $request->user()->tenants()->first();
        $request->session()->put('current_tenant_id', $tenant?->id);

        return redirect()->intended('/app/dashboard');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'company_name' => ['required', 'string', 'max:255'],
            'industry' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:255'],
            'tagline' => ['nullable', 'string', 'max:255'],
            'template' => ['nullable', 'string'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);

        $slug = $this->uniqueSlug(Str::slug($data['company_name'] ?: $user->name));

        $tenant = Tenant::create([
            'name' => $data['company_name'],
            'slug' => $slug,
            'industry' => $data['industry'] ?? null,
            'country' => $data['country'] ?? null,
            'plan_id' => Plan::where('slug', 'starter')->first()?->id,
            'status' => 'active',
        ]);

        TenantContext::set($tenant->id);

        $tenant->users()->attach($user->id, ['role' => 'owner']);

        BusinessProfile::create([
            'tenant_id' => $tenant->id,
            'name' => $data['company_name'],
            'tagline' => $data['tagline'] ?? null,
            'industry' => $data['industry'] ?? null,
            'contact' => ['email' => $data['email']],
        ]);

        Agent::create([
            'tenant_id' => $tenant->id,
            'slug' => 'assistant',
            'name' => "Asistente de {$data['company_name']}",
            'instructions' => "Eres el asistente virtual de {$data['company_name']}. Responde en español, con tono amable y profesional, ayudando a los visitantes con dudas sobre la empresa.",
            'tools' => [],
            'is_active' => true,
        ]);

        app(WebsiteBuilderService::class)->createSite($tenant, $data['template'] ?? 'minimal-business', $data['company_name']);

        Auth::login($user);
        $request->session()->put('current_tenant_id', $tenant->id);

        return redirect('/app/builder');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    private function uniqueSlug(string $slug): string
    {
        $base = $slug ?: 'empresa';
        $candidate = $base;
        $i = 1;

        while (Tenant::query()->where('slug', $candidate)->exists()) {
            $candidate = $base.'-'.$i++;
        }

        return $candidate;
    }
}
