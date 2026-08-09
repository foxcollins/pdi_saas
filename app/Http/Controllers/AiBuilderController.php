<?php

namespace App\Http\Controllers;

use App\Services\Site\WebsiteBuilderService;
use Illuminate\Http\Request;

class AiBuilderController extends Controller
{
    public function generate(Request $request)
    {
        $answers = $request->validate([
            'company_name' => ['nullable', 'string', 'max:255'],
            'industry' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:3000'],
            'services' => ['nullable', 'string', 'max:2000'],
            'style' => ['nullable', 'string', 'max:255'],
        ]);

        $profile = tenant()->profile;
        $builder = app(WebsiteBuilderService::class);

        $config = $builder->generateWithAi($profile, $answers);
        $website = $builder->applyGeneratedConfig(tenant(), $config);

        $home = collect($website->pages)->first(fn ($p) => $p['slug'] === 'home') ?? $website->pages[0];

        return response()->json([
            'ok' => true,
            'page' => $home,
            'theme' => $website->theme,
            'template' => $website->template,
        ]);
    }

    public function refine(Request $request)
    {
        $request->validate(['instruction' => ['required', 'string', 'max:500']]);

        $website = tenant()->website;

        if (! $website) {
            return response()->json(['error' => 'No hay sitio.'], 404);
        }

        $home = collect($website->pages)->first(fn ($p) => $p['slug'] === 'home') ?? $website->pages[0];

        $config = [
            'template' => $website->template,
            'theme' => $website->theme,
            'sections' => $home['sections'] ?? [],
        ];

        $builder = app(WebsiteBuilderService::class);
        $refined = $builder->refine($config, $request->instruction);

        $refinedPage = array_merge($home, ['sections' => $refined['sections'] ?? $home['sections']]);
        $pages = collect($website->pages)
            ->map(fn ($p) => $p['slug'] === 'home' ? $refinedPage : $p)
            ->all();

        $website->update([
            'pages' => $pages,
            'theme' => array_merge($website->theme, $refined['theme'] ?? []),
        ]);

        return response()->json([
            'ok' => true,
            'page' => $refinedPage,
            'theme' => $website->theme,
        ]);
    }

    public function fillFromProfile()
    {
        $builder = app(WebsiteBuilderService::class);
        $website = $builder->createSite(tenant(), tenant()->website?->template ?? 'minimal-business');

        $home = collect($website->pages)->first(fn ($p) => $p['slug'] === 'home') ?? $website->pages[0];

        return response()->json(['ok' => true, 'page' => $home, 'theme' => $website->theme]);
    }
}
