<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Website;
use App\Services\Site\WebsiteBuilderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BuilderFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_builder_guarda_y_publica_el_sitio(): void
    {
        $user = User::factory()->create();
        $tenant = $this->makeTenant('Builder Co', 'builder-co');
        $tenant->users()->attach($user->id, ['role' => 'owner']);
        $this->switchTenant($tenant);

        app(WebsiteBuilderService::class)->createSite($tenant, 'minimal-business', 'Builder Co');

        $this->actingAs($user)->withSession(['current_tenant_id' => $tenant->id]);

        $this->get('/app/builder')->assertStatus(200);

        $page = [
            'slug' => 'home',
            'title' => 'Inicio',
            'meta' => ['title' => 'Builder Co', 'description' => ''],
            'sections' => [
                [
                    'id' => 's-1',
                    'type' => 'hero',
                    'variant' => 'default',
                    'content' => ['title' => 'Hola', 'subtitle' => 'Bienvenido'],
                    'settings' => [],
                ],
            ],
        ];

        $this->post('/app/builder/save', ['page' => $page, 'theme' => ['primary' => '#123456']])
            ->assertOk()
            ->assertJson(['ok' => true]);

        $website = $tenant->website->refresh();

        $this->assertSame('#123456', $website->theme['primary'] ?? null);
        $this->assertSame('Hola', $website->pages[0]['sections'][0]['content']['title']);

        $this->post('/app/builder/publish');

        $website->refresh();

        $this->assertSame('live', $website->status);
        $this->assertNotNull($website->published_at);
    }

    public function test_el_builder_no_afecta_el_sitio_de_otro_tenant(): void
    {
        $user = User::factory()->create();
        $a = $this->makeTenant('Builder A', 'builder-a');
        $b = $this->makeTenant('Builder B', 'builder-b');
        $a->users()->attach($user->id, ['role' => 'owner']);
        $this->switchTenant($a);

        app(WebsiteBuilderService::class)->createSite($a, 'minimal-business', 'Builder A');

        $this->switchTenant($b);
        app(WebsiteBuilderService::class)->createSite($b, 'minimal-business', 'Builder B');
        $before = $b->website->refresh()->theme['primary'] ?? null;

        $this->switchTenant($a);
        $this->actingAs($user)->withSession(['current_tenant_id' => $a->id]);

        $page = [
            'slug' => 'home',
            'title' => 'Inicio',
            'meta' => ['title' => 'Builder A', 'description' => ''],
            'sections' => [],
        ];

        $this->post('/app/builder/save', ['page' => $page, 'theme' => ['primary' => '#abcdef']])
            ->assertOk();

        $this->assertSame('#abcdef', $a->website->refresh()->theme['primary'] ?? null);

        $siteB = Website::withoutGlobalScopes()->where('tenant_id', $b->id)->firstOrFail();
        $this->assertSame($before, $siteB->theme['primary'] ?? null);
    }
}
