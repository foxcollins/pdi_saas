<?php

namespace App\Services\Site;

use App\Models\BusinessProfile;
use App\Models\Tenant;
use App\Models\Website;
use Illuminate\Support\Str;

class WebsiteBuilderService
{
    public function createSite(Tenant $tenant, string $templateSlug, ?string $name = null): Website
    {
        $template = config("site.templates.{$templateSlug}");

        if (! $template) {
            throw new \InvalidArgumentException("Template no existe: {$templateSlug}");
        }

        $profile = $tenant->profile;

        $sections = $this->buildSections($template['sections'] ?? [], $profile);
        $theme = array_merge(config('site.default_theme'), $template['theme'] ?? []);

        $website = Website::updateOrCreate(
            ['tenant_id' => $tenant->id],
            [
                'name' => $name ?: $tenant->name,
                'template' => $templateSlug,
                'theme' => $theme,
                'pages' => [
                    $this->homePage($sections, $profile),
                ],
                'status' => 'draft',
            ]
        );

        return $website;
    }

    public function applyGeneratedConfig(Tenant $tenant, array $config): Website
    {
        $website = $tenant->website ?? Website::create(['tenant_id' => $tenant->id, 'name' => $tenant->name]);

        $templateSlug = $config['template'] ?? $website->template;
        $template = config("site.templates.{$templateSlug}");
        $sections = $this->normalizeSections($config['sections'] ?? [], $tenant->profile);
        $theme = array_merge(config('site.default_theme'), $config['theme'] ?? [], $template['theme'] ?? []);

        $website->update([
            'template' => $templateSlug,
            'theme' => $theme,
            'pages' => [$this->homePage($sections, $tenant->profile)],
            'status' => 'draft',
        ]);

        return $website;
    }

    public function generateWithAi(BusinessProfile $profile, array $answers): array
    {
        $industryKeywords = mb_strtolower(implode(' ', $answers));

        $templateSlug = $this->matchTemplate($industryKeywords, $profile?->industry);

        if (ai()->isFake()) {
            return $this->generateFake($profile, $answers, $templateSlug);
        }

        $template = config("site.templates.{$templateSlug}");
        $sections = $this->buildSections($template['sections'] ?? [], $profile);

        $prompt = $this->buildGeneratePrompt($profile, $answers, $templateSlug, $sections);

        $raw = ai()->chat([
            ['role' => 'system', 'content' => 'Eres un diseñador web experto. Genera una configuración de sitio web en JSON válido, sin texto adicional.'],
            ['role' => 'user', 'content' => $prompt],
        ], ['max_tokens' => 3000]);

        $decoded = $this->decodeJson($raw);

        return [
            'template' => $templateSlug,
            'theme' => array_merge(config('site.default_theme'), $template['theme'] ?? [], $decoded['theme'] ?? []),
            'sections' => $decoded['sections'] ?? $sections,
        ];
    }

    public function refine(array $config, string $instruction): array
    {
        if (ai()->isFake()) {
            return $this->refineFake($config, $instruction);
        }

        $prompt = <<<PROMPT
        Tienes esta configuración de sitio web (JSON): 
        {json_encode($config)}

        El cliente pidió: "$instruction"

        Devuelve SOLO el JSON modificado con la misma estructura (template, theme, sections con type/variant/content). No añadas texto.
        PROMPT;

        $raw = ai()->chat([
            ['role' => 'system', 'content' => 'Eres un diseñador web experto que ajusta configuraciones JSON de sitios.'],
            ['role' => 'user', 'content' => $prompt],
        ], ['max_tokens' => 3000]);

        $decoded = $this->decodeJson($raw);

        return array_merge($config, $decoded);
    }

    private function generateFake(BusinessProfile $profile, array $answers, string $templateSlug): array
    {
        $template = config("site.templates.{$templateSlug}");
        $sections = $this->buildSections($template['sections'] ?? [], $profile);

        $companyName = trim($answers['company_name'] ?? $profile->name ?? 'Tu empresa');
        $tagline = trim($answers['tagline'] ?? $profile->tagline ?? 'Tu empresa, con presencia digital inteligente');
        $description = trim($answers['description'] ?? $profile->description ?? '');
        $servicesRaw = explode(',', $answers['services'] ?? '');
        $services = array_values(array_filter(array_map('trim', $servicesRaw)));

        foreach ($sections as &$section) {
            $c = &$section['content'];
            if ($section['type'] === 'hero') {
                $c['badge'] = $profile->industry ?: 'Negocio local';
                $c['title'] = $tagline;
                $c['subtitle'] = $description ?: 'Describimos brevemente lo que hacemos y a quién ayudamos.';
            }
            if ($section['type'] === 'about') {
                $c['text'] = $description ?: 'Conoce nuestra historia y por qué nuestros clientes confían en nosotros.';
            }
            if ($section['type'] === 'services' && $services) {
                $c['items'] = collect($services)->map(fn ($s) => ['icon' => 'sparkles', 'title' => $s, 'description' => 'Detalle del servicio '.$s.'.'])->all();
            }
        }
        unset($section);

        return [
            'template' => $templateSlug,
            'theme' => array_merge(config('site.default_theme'), $template['theme'] ?? []),
            'sections' => $sections,
        ];
    }

    private function refineFake(array $config, string $instruction): array
    {
        $i = mb_strtolower($instruction);
        $theme = $config['theme'] ?? [];
        $sections = $config['sections'] ?? [];

        if (str_contains($i, 'premium') || str_contains($i, 'elegant') || str_contains($i, 'lujoso')) {
            $theme['radius'] = 'full';
            $theme['font'] = 'Playfair Display';
            $theme['button_style'] = 'outline';
        }

        if (str_contains($i, 'apple') || str_contains($i, 'minimal') || str_contains($i, 'limpio')) {
            $theme['background'] = '#ffffff';
            $theme['text'] = '#111827';
            $theme['primary'] = '#111827';
            $theme['radius'] = 'large';
        }

        if (str_contains($i, 'tecnol') || str_contains($i, 'tech') || str_contains($i, 'moderno')) {
            $theme['background'] = '#0b1220';
            $theme['text'] = '#e2e8f0';
            $theme['primary'] = '#0ea5e9';
            $theme['font'] = 'Space Grotesk';
        }

        if (str_contains($i, 'productos antes') || str_contains($i, 'productos primero')) {
            $positions = [];
            foreach ($sections as $idx => $s) {
                $positions[$s['type']] = $idx;
            }
            if (isset($positions['products'], $positions['services']) && $positions['products'] > $positions['services']) {
                $sections = $this->moveBefore($sections, 'products', 'services');
            }
        }

        if (str_contains($i, 'servicios antes') || str_contains($i, 'servicios primero')) {
            $positions = [];
            foreach ($sections as $idx => $s) {
                $positions[$s['type']] = $idx;
            }
            if (isset($positions['services'], $positions['products']) && $positions['services'] > $positions['products']) {
                $sections = $this->moveBefore($sections, 'services', 'products');
            }
        }

        if (str_contains($i, 'certificac') && ! collect($sections)->contains('type', 'certifications')) {
            $sections[] = [
                'id' => (string) Str::uuid(),
                'type' => 'cta',
                'variant' => 'banner',
                'content' => ['title' => 'Certificaciones', 'text' => 'Contamos con certificaciones y garantías de calidad.', 'button' => ['label' => 'Contactar', 'url' => '#contacto']],
                'settings' => [],
            ];
        }

        $config['theme'] = $theme;
        $config['sections'] = $sections;

        return $config;
    }

    private function buildSections(array $definitions, ?BusinessProfile $profile): array
    {
        return collect($definitions)
            ->map(fn (array $def) => [
                'id' => (string) Str::uuid(),
                'type' => $def['type'],
                'variant' => $def['variant'] ?? 'default',
                'content' => $this->hydrate($def['type'], config("site.blocks.{$def['type']}.variants.".($def['variant'] ?? 'default').'.content') ?? [], $profile),
                'settings' => [],
            ])
            ->values()
            ->all();
    }

    private function normalizeSections(array $sections, ?BusinessProfile $profile): array
    {
        return collect($sections)
            ->map(fn ($s) => [
                'id' => $s['id'] ?? (string) Str::uuid(),
                'type' => $s['type'],
                'variant' => $s['variant'] ?? 'default',
                'content' => $this->hydrate($s['type'], $s['content'] ?? [], $profile),
                'settings' => $s['settings'] ?? [],
            ])
            ->values()
            ->all();
    }

    private function hydrate(string $type, array $content, ?BusinessProfile $profile): array
    {
        if (! $profile) {
            return $content;
        }

        $services = collect($profile->services ?: [])->values();
        $products = collect($profile->products ?: [])->values();
        $faqs = collect($profile->faqs ?: [])->values();
        $team = collect($profile->team ?: [])->values();

        switch ($type) {
            case 'hero':
                $content['badge'] = $content['badge'] ?: ($profile->industry ?: '');
                $content['title'] = $content['title'] && ! str_contains($content['title'], 'presencia digital inteligente') && $content['title'] !== 'Tu empresa, con presencia digital inteligente' ? $content['title'] : ($profile->tagline ?: 'Tu empresa, con presencia digital inteligente');
                $content['subtitle'] = $content['subtitle'] ?: ($profile->description ?: '');
                break;
            case 'about':
                $content['text'] = $content['text'] ?: ($profile->description ?: '');
                break;
            case 'services':
                if ($services->count() && (blank($content['items'][0]['title'] ?? null) || str_contains($content['items'][0]['title'] ?? '', 'Servicio 1'))) {
                    $content['items'] = $services->map(fn ($s) => ['icon' => 'sparkles', 'title' => $s['title'] ?? 'Servicio', 'description' => $s['description'] ?? ''])->all();
                }
                break;
            case 'products':
                if ($products->count() && (blank($content['items'][0]['title'] ?? null) || str_contains($content['items'][0]['title'] ?? '', 'Producto 1'))) {
                    $content['items'] = $products->map(fn ($p) => ['image' => $p['image'] ?? '', 'title' => $p['title'] ?? 'Producto', 'description' => $p['description'] ?? '', 'price' => $p['price'] ?? ''])->all();
                }
                break;
            case 'team':
                if ($team->count()) {
                    $content['items'] = $team->map(fn ($t) => ['name' => $t['name'] ?? '', 'role' => $t['role'] ?? '', 'photo' => $t['photo'] ?? ''])->all();
                }
                break;
            case 'faq':
                if ($faqs->count()) {
                    $content['items'] = $faqs->map(fn ($f) => ['q' => $f['q'] ?? $f['question'] ?? '', 'a' => $f['a'] ?? $f['answer'] ?? ''])->all();
                }
                break;
            case 'contact':
                $contact = $profile->contact ?: [];
                $content = array_merge($content, [
                    'phone' => $contact['phone'] ?? '',
                    'whatsapp' => $contact['whatsapp'] ?? '',
                    'email' => $contact['email'] ?? '',
                    'address' => $contact['address'] ?? '',
                    'schedule' => $profile->schedule ?: [],
                ]);
                break;
            case 'navbar':
                $content['logo'] = $content['logo'] ?: $profile->logo_url;
                break;
        }

        return $content;
    }

    private function homePage(array $sections, ?BusinessProfile $profile): array
    {
        $name = $profile?->name ?: 'Mi empresa';

        return [
            'slug' => 'home',
            'title' => 'Inicio',
            'meta' => ['title' => $name, 'description' => $profile?->description ?: ''],
            'sections' => $sections,
        ];
    }

    private function matchTemplate(string $keywords, ?string $industry): string
    {
        $candidates = [
            'restaurant' => ['restaurante', 'restaurant', 'comida', 'cafe', 'gastronom', 'cocina', 'pizza', 'bar'],
            'beauty-clinic' => ['clinic', 'clinica', 'salud', 'medic', 'estetic', 'belleza', 'spa', 'salon', 'dental', 'fisio'],
            'realty' => ['inmobiliar', 'real estate', 'propiedad', 'bienes raices', 'departament', 'casas'],
            'startup-saas' => ['software', 'saas', 'startup', 'app', 'digital', 'tech', 'marketing', 'consultora', 'desarrollo'],
            'minimal-business' => ['servicio', 'servicios', 'abogad', 'contador', 'consultor', 'profesional', 'finanza'],
            'modern-tech' => ['industria', 'industrial', 'ingenieria', 'manufactura', 'fabric', 'bombas', 'hidraulica', 'maquinaria'],
        ];

        $haystack = $keywords.' '.strtolower($industry ?? '');

        $best = null;
        $bestScore = 0;

        foreach ($candidates as $slug => $terms) {
            $score = 0;
            foreach ($terms as $term) {
                if (str_contains($haystack, $term)) {
                    $score++;
                }
            }
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $slug;
            }
        }

        return $best ?: 'modern-tech';
    }

    private function moveBefore(array $sections, string $type, string $target): array
    {
        $item = null;
        $sections = collect($sections);

        $index = $sections->search(fn ($s) => $s['type'] === $type);
        $targetIndex = $sections->search(fn ($s) => $s['type'] === $target);

        if ($index === false || $targetIndex === false) {
            return $sections->all();
        }

        $item = $sections->pull($index);
        $sections = $sections->values();

        $targetIndex = $sections->search(fn ($s) => $s['type'] === $target);

        return $sections->splice($targetIndex, 0, [$item])->all();
    }

    private function buildGeneratePrompt(BusinessProfile $profile, array $answers, string $templateSlug, array $sections): string
    {
        $industry = $answers['industry'] ?? $profile->industry ?? '';
        $description = $answers['description'] ?? $profile->description ?? '';
        $services = $answers['services'] ?? '';
        $style = $answers['style'] ?? 'moderno';
        $sectionsJson = json_encode($sections);

        return <<<PROMPT
        Empresa: {$profile->name}
        Industria: {$industry}
        Descripción: {$description}
        Servicios: {$services}
        Estilo deseado: {$style}

        Template base: {$templateSlug}
        Secciones iniciales: {$sectionsJson}

        Genera la configuración final (template, theme, sections con content en español) adaptada a la empresa.
        PROMPT;
    }

    private function decodeJson(string $raw): array
    {
        $raw = trim($raw);

        if (preg_match('/```(?:json)?\s*(.*?)\s*```/s', $raw, $m)) {
            $raw = $m[1];
        }

        $decoded = json_decode($raw, true);

        if (! is_array($decoded)) {
            $start = strpos($raw, '{');
            $end = strrpos($raw, '}');
            if ($start !== false && $end !== false && $end > $start) {
                $decoded = json_decode(substr($raw, $start, $end - $start + 1), true);
            }
        }

        return is_array($decoded) ? $decoded : [];
    }
}
