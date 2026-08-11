<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\Plan;
use App\Models\Product;
use App\Models\User;
use App\Services\Agents\AgentPresetService;
use App\Services\Agents\AgentRouter;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgentsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PlanSeeder::class);
    }

    private function authTenant(string $slug = 'agents-demo', string $planSlug = 'pro'): array
    {
        $user = User::factory()->create();
        $tenant = $this->makeTenant('Agents Demo', $slug);
        $tenant->users()->attach($user->id, ['role' => 'owner']);
        $tenant->update(['plan_id' => Plan::where('slug', $planSlug)->value('id')]);
        $this->switchTenant($tenant);
        $this->actingAs($user)->withSession(['current_tenant_id' => $tenant->id]);
        app(AgentPresetService::class)->ensureForTenant($tenant);

        return [$user, $tenant];
    }

    public function test_el_panel_muestra_todos_los_presets_de_agentes(): void
    {
        $this->authTenant('agents-page');

        $this->get('/app/agents')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Agents')
                ->has('agents', 6)
                ->has('allowed_tools')
                ->has('tools_catalog'));
    }

    public function test_el_panel_puede_activar_y_configurar_un_agente(): void
    {
        [$user, $tenant] = $this->authTenant('agents-update');

        $sales = Agent::query()->where('slug', 'sales')->firstOrFail();

        $this->put("/app/agents/{$sales->id}", [
            'name' => 'Ventas VIP',
            'description' => 'Atención comercial',
            'instructions' => 'Cotiza siempre con descuento.',
            'trigger_keywords' => ['precio', 'comprar'],
            'tools' => ['catalog_lookup', 'create_lead'],
            'is_active' => true,
        ])->assertRedirect()->assertSessionHas('success');

        $sales->refresh();

        $this->assertSame('Ventas VIP', $sales->name);
        $this->assertTrue($sales->is_active);
        $this->assertSame(['precio', 'comprar'], $sales->trigger_keywords);
        $this->assertSame(['catalog_lookup', 'create_lead'], $sales->tools);
    }

    public function test_el_panel_rechaza_tools_fuera_del_plan(): void
    {
        $this->authTenant('agents-plan', 'starter');

        $sales = Agent::query()->where('slug', 'sales')->firstOrFail();

        $this->put("/app/agents/{$sales->id}", [
            'name' => 'Ventas',
            'tools' => ['n8n_webhook'],
            'is_active' => true,
        ])->assertSessionHasErrors('tools.0');
    }

    public function test_el_router_elige_el_agente_de_ventas_por_intencion(): void
    {
        [, $tenant] = $this->authTenant('agents-sales');
        Agent::query()->where('slug', 'sales')->update(['is_active' => true]);

        $agent = app(AgentRouter::class)->resolve($tenant, '¿Cuánto cuesta una bomba?');

        $this->assertSame('sales', $agent->slug);
    }

    public function test_el_router_elige_el_agente_de_soporte_por_intencion(): void
    {
        [, $tenant] = $this->authTenant('agents-support');
        Agent::query()->where('slug', 'support')->update(['is_active' => true]);

        $agent = app(AgentRouter::class)->resolve($tenant, 'Mi producto no funciona, tiene una falla');

        $this->assertSame('support', $agent->slug);
    }

    public function test_el_router_cae_al_agente_general_cuando_no_calza_intencion(): void
    {
        [, $tenant] = $this->authTenant('agents-general');

        $agent = app(AgentRouter::class)->resolve($tenant, '¿Cuál es su horario de atención?');

        $this->assertSame('assistant', $agent->slug);
    }

    public function test_el_router_ignora_agentes_inactivos(): void
    {
        [, $tenant] = $this->authTenant('agents-inactive');

        $agent = app(AgentRouter::class)->resolve($tenant, '¿Cuánto cuesta una bomba?');

        $this->assertSame('assistant', $agent->slug);
    }

    public function test_el_chat_cotiza_con_el_agente_de_ventas_activo(): void
    {
        $this->authTenant('agents-chat');
        Agent::query()->where('slug', 'sales')->update([
            'is_active' => true,
            'tools' => ['catalog_lookup', 'quote_calculator', 'create_quote', 'create_lead'],
        ]);
        Product::create(['title' => 'Bomba centrífuga HC', 'price' => 2400, 'currency' => 'USD', 'is_active' => true]);

        $response = $this->post('/api/chat/'.tenant()->slug, [
            'message' => 'Quiero cotizar una bomba',
            'name' => 'Pedro',
        ]);

        $response->assertOk();
        $this->assertStringContainsString('"type":"done"', $response->streamedContent());
        $this->assertDatabaseHas('quotes', ['status' => 'draft']);
    }
}
