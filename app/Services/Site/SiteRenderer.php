<?php

namespace App\Services\Site;

use App\Models\Tenant;

class SiteRenderer
{
    public function render(Tenant $tenant, ?string $pageSlug = null): ?array
    {
        $website = $tenant->website;

        if (! $website) {
            return null;
        }

        $profile = $tenant->profile;
        $agent = $tenant->agents()->where('slug', 'assistant')->first();
        $agentGuardrails = $agent?->guardrails ?? [];
        $pages = $website->pages ?: [];

        $page = collect($pages)->firstWhere('slug', $pageSlug)
            ?? collect($pages)->first(fn ($p) => $p['slug'] === 'home')
            ?? $pages[0]
            ?? ['slug' => 'home', 'title' => 'Inicio', 'meta' => [], 'sections' => []];
        $page = $this->normalizeMediaUrls($page);

        $theme = $website->theme;

        return [
            'site' => [
                'name' => $profile?->name ?: $tenant->name,
                'slug' => $tenant->slug,
                'logo' => $profile?->logo_url,
                'template' => $website->template,
                'theme' => $theme,
                'chat' => [
                    'enabled' => $theme['chat_enabled'] ?? true,
                    'title' => $agent?->name ?: ($theme['chat_title'] ?? 'Asistente virtual'),
                    'welcome' => $agentGuardrails['welcome'] ?? ($theme['chat_welcome'] ?? 'Hola, ¿en qué puedo ayudarte?'),
                ],
                'status' => $website->status,
            ],
            'page' => $page,
            'profile' => $this->publicProfile($profile),
            'published' => $website->isLive(),
        ];
    }

    private function publicProfile(?object $profile): array
    {
        if (! $profile) {
            return [];
        }

        return [
            'name' => $profile->name,
            'tagline' => $profile->tagline,
            'description' => $profile->description,
            'contact' => $profile->contact,
            'social' => $profile->social,
            'schedule' => $profile->schedule,
            'branches' => $profile->branches,
        ];
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
}
