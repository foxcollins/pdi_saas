<?php

namespace App\Http\Controllers;

use App\Models\BusinessProfile;
use Illuminate\Http\Request;

class ContentController extends Controller
{
    public function show()
    {
        $profile = tenant()->profile;

        if (! $profile) {
            $profile = BusinessProfile::create([
                'tenant_id' => tenant()->id,
                'name' => tenant()->name,
                'contact' => ['email' => auth()->user()->email],
            ]);
        }

        return inertia('Content', ['profile' => $profile->toArray()]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'tagline' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'industry' => ['nullable', 'string', 'max:255'],
            'logo_url' => ['nullable', 'string', 'max:500'],
            'services' => ['nullable', 'array'],
            'products' => ['nullable', 'array'],
            'branches' => ['nullable', 'array'],
            'schedule' => ['nullable', 'array'],
            'contact' => ['nullable', 'array'],
            'social' => ['nullable', 'array'],
            'faqs' => ['nullable', 'array'],
            'team' => ['nullable', 'array'],
            'certifications' => ['nullable', 'array'],
        ]);

        $profile = tenant()->profile ?? BusinessProfile::create(['tenant_id' => tenant()->id, 'name' => tenant()->name]);

        $profile->update($data);

        return back()->with('success', 'Perfil actualizado.');
    }
}
