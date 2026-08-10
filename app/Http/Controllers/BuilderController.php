<?php

namespace App\Http\Controllers;

use App\Services\Site\WebsiteBuilderService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class BuilderController extends Controller
{
    public function show()
    {
        $website = tenant()->website;

        if (! $website) {
            $website = app(WebsiteBuilderService::class)->createSite(tenant(), 'minimal-business');
        }

        $pages = $website->pages ?: [];
        $home = collect($pages)->first(fn ($p) => $p['slug'] === 'home') ?? $pages[0] ?? ['slug' => 'home', 'title' => 'Inicio', 'sections' => []];
        $home = $this->normalizeMediaUrls($home);

        return inertia('Builder', [
            'site' => [
                'id' => $website->id,
                'name' => $website->name,
                'template' => $website->template,
                'theme' => $website->theme,
                'status' => $website->status,
                'published_at' => $website->published_at?->toIso8601String(),
            ],
            'page' => $home,
            'catalog' => [
                'templates' => config('site.templates'),
                'blocks' => config('site.blocks'),
                'fonts' => config('site.fonts'),
                'radius_options' => config('site.radius_options'),
                'button_styles' => config('site.button_styles'),
                'animations' => config('site.animations'),
                'default_theme' => config('site.default_theme'),
            ],
        ]);
    }

    public function save(Request $request)
    {
        $data = $request->validate([
            'page' => ['required', 'array'],
            'theme' => ['required', 'array'],
        ]);

        $data['page'] = $this->normalizeMediaUrls($data['page']);
        $this->validatePageConfig($data['page']);

        if (strlen(json_encode($data)) > 500000) {
            throw ValidationException::withMessages(['page' => 'La configuración del sitio es demasiado grande.']);
        }

        $website = tenant()->website;

        if (! $website) {
            return response()->json(['error' => 'Sitio no encontrado'], 404);
        }

        $pages = collect($website->pages ?: [])
            ->map(fn ($p) => $p['slug'] === ($data['page']['slug'] ?? 'home') ? $data['page'] : $p)
            ->all();

        if (! collect($pages)->contains('slug', $data['page']['slug'] ?? 'home')) {
            $pages[] = $data['page'];
        }

        $website->update([
            'pages' => $pages,
            'theme' => $data['theme'],
        ]);

        return response()->json(['ok' => true]);
    }

    private function validatePageConfig(array $page): void
    {
        $errors = [];

        foreach ($page['sections'] ?? [] as $index => $section) {
            $type = $section['type'] ?? null;
            $variant = $section['variant'] ?? null;
            $definition = $type ? config("site.blocks.{$type}") : null;

            if (! is_array($definition)) {
                $errors["page.sections.{$index}.type"] = 'El bloque no existe.';

                continue;
            }

            if (! isset($definition['variants'][$variant])) {
                $errors["page.sections.{$index}.variant"] = 'La variante del bloque no existe.';
            }

            $this->validateUrls($section['content'] ?? [], "page.sections.{$index}.content", $errors);
        }

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function normalizeMediaUrls(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map(fn ($item) => $this->normalizeMediaUrls($item), $value);
        }

        if (! is_string($value)) {
            return $value;
        }

        return preg_replace('~^https?://[^/]+(/storage/.*)$~i', '$1', $value) ?? $value;
    }

    private function validateUrls(mixed $value, string $path, array &$errors): void
    {
        if (is_array($value)) {
            foreach ($value as $key => $child) {
                $this->validateUrls($child, "{$path}.{$key}", $errors);
            }

            return;
        }

        if (! is_string($value) || ! preg_match('/(^|url|image|logo|photo|embed_url)$/i', $path)) {
            return;
        }

        $trimmed = trim($value);

        if ($trimmed !== '' && ! preg_match('/^(https?:\/\/|#[a-zA-Z0-9_-]+|\/)/', $trimmed)) {
            $errors[$path] = 'La URL debe usar http, https, un ancla o una ruta local.';
        }
    }

    public function applyTemplate(Request $request)
    {
        $request->validate(['template' => ['required', 'string']]);

        $website = app(WebsiteBuilderService::class)->createSite(tenant(), $request->template);

        return back()->with('success', 'Template aplicado.');
    }

    public function publish()
    {
        $website = tenant()->website;

        if (! $website) {
            return back()->withErrors(['error' => 'No hay sitio que publicar.']);
        }

        $website->update([
            'status' => $website->isLive() ? 'draft' : 'live',
            'published_at' => $website->isLive() ? null : now(),
        ]);

        return back()->with('success', $website->isLive() ? 'Sitio publicado.' : 'Sitio pasado a borrador.');
    }

    public function reorder(Request $request)
    {
        $data = $request->validate([
            'page' => ['required', 'array'],
        ]);

        $website = tenant()->website;

        if (! $website) {
            return response()->json(['error' => 'Sitio no encontrado'], 404);
        }

        $pages = collect($website->pages ?: [])
            ->map(fn ($p) => $p['slug'] === ($data['page']['slug'] ?? 'home') ? $data['page'] : $p)
            ->all();

        $website->update(['pages' => $pages]);

        return response()->json(['ok' => true]);
    }
}
