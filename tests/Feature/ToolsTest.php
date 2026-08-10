<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Plan;
use App\Models\Product;
use App\Models\User;
use App\Services\Billing\PlanService;
use App\Services\Quotes\QuoteService;
use App\Services\Site\WebsiteBuilderService;
use App\Services\Tools\ToolContext;
use App\Services\Tools\ToolException;
use App\Services\Tools\ToolManager;
use App\Services\Tools\ToolOrchestrator;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ToolsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPlans();
    }

    private function authTenant(string $slug = 'tools-demo', string $planSlug = 'pro'): array
    {
        $user = User::factory()->create();
        $tenant = $this->makeTenant('Tools Demo', $slug);
        $tenant->users()->attach($user->id, ['role' => 'owner']);
        $tenant->update(['plan_id' => Plan::where('slug', $planSlug)->value('id')]);
        $this->switchTenant($tenant);
        $this->actingAs($user)->withSession(['current_tenant_id' => $tenant->id]);

        return [$user, $tenant];
    }

    private function seedPlans(): void
    {
        $this->seed(PlanSeeder::class);
    }

    private function agentWithTools(array $tools = ['catalog_lookup', 'quote_calculator', 'create_quote', 'create_lead', 'create_task', 'n8n_webhook']): Agent
    {
        return Agent::query()->firstOrCreate(
            ['tenant_id' => tenant()->id, 'slug' => 'assistant'],
            ['name' => 'Asistente', 'instructions' => 'Responde en español.', 'tools' => $tools, 'is_active' => true, 'guardrails' => []]
        );
    }

    private function makeProducts(): array
    {
        $a = Product::create(['title' => 'Bomba centrífuga HC', 'price' => 2400, 'currency' => 'USD', 'category' => 'Bombas', 'is_active' => true]);
        $b = Product::create(['title' => 'Kit de repuestos estándar', 'price' => 120, 'currency' => 'USD', 'category' => 'Repuestos', 'is_active' => true]);
        $c = Product::create(['title' => 'Sistema de bombeo solar', 'price' => 1800, 'currency' => 'USD', 'category' => 'Sistemas', 'is_active' => false]);

        return [$a, $b, $c];
    }

    public function test_la_pagina_de_tools_muestra_catalogo_cotizaciones_y_tools(): void
    {
        $this->authTenant('tools-page');
        $this->agentWithTools(['catalog_lookup']);
        $this->makeProducts();

        $this->get('/app/tools')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Tools')
                ->has('tools', 7)
                ->has('products', 3)
                ->where('enabled.0', 'catalog_lookup')
                ->has('quotes', 0));
    }

    public function test_catalog_lookup_busca_productos_activos_del_tenant(): void
    {
        [$user, $tenant] = $this->authTenant('tools-catalog');
        $this->agentWithTools();
        [$a, $b] = $this->makeProducts();

        $context = new ToolContext($tenant, $this->agentWithTools());

        $result = app(ToolManager::class)->run('catalog_lookup', ['query' => 'bomba'], $context);

        $this->assertSame(1, $result['count']);
        $this->assertSame('Bomba centrífuga HC', $result['items'][0]['title']);

        $result2 = app(ToolManager::class)->run('catalog_lookup', ['query' => 'repuestos'], $context);

        $this->assertSame(1, $result2['count']);
        $this->assertDatabaseHas('tool_runs', ['tool' => 'catalog_lookup', 'status' => 'ok']);
    }

    public function test_catalog_lookup_no_filtra_productos_de_otro_tenant(): void
    {
        [, $tenantA] = $this->authTenant('tools-cat-a');
        $this->makeProducts();

        $tenantB = $this->makeTenant('Otro', 'tools-cat-b');
        $this->switchTenant($tenantB);
        $this->agentWithTools();

        $context = new ToolContext($tenantB, $this->agentWithTools());
        $result = app(ToolManager::class)->run('catalog_lookup', ['query' => 'bomba'], $context);

        $this->assertSame(0, $result['count']);
    }

    public function test_quote_calculator_calcula_subtotal_impuestos_y_total(): void
    {
        [, $tenant] = $this->authTenant('tools-quote-calc');
        $this->agentWithTools();
        [$a, $b] = $this->makeProducts();

        $context = new ToolContext($tenant, $this->agentWithTools());
        $result = app(ToolManager::class)->run('quote_calculator', [
            'items' => [
                ['product_id' => $a->id, 'quantity' => 2],
                ['product_id' => $b->id, 'quantity' => 1],
            ],
            'tax_rate' => 18,
        ], $context);

        $this->assertSame(4920.0, $result['subtotal']);
        $this->assertSame(18.0, $result['tax_rate']);
        $this->assertSame(885.6, $result['tax_amount']);
        $this->assertSame(5805.6, $result['total']);
        $this->assertCount(2, $result['items']);
    }

    public function test_create_quote_emite_una_cotizacion_con_pdf(): void
    {
        Storage::fake('local');

        [$user, $tenant] = $this->authTenant('tools-quote-create');
        $this->agentWithTools();
        [$a, $b] = $this->makeProducts();

        $contact = Contact::create(['name' => 'Juan', 'email' => 'juan@example.com', 'phone' => '+51999000111']);

        $quote = app(QuoteService::class)->create($tenant, [
            'items' => [
                ['product_id' => $a->id, 'title' => 'Bomba centrífuga HC', 'quantity' => 1, 'unit_price' => 2400, 'amount' => 2400],
                ['product_id' => $b->id, 'title' => 'Kit de repuestos estándar', 'quantity' => 2, 'unit_price' => 120, 'amount' => 240],
            ],
            'tax_rate' => 18,
            'notes' => 'Cotización automática',
        ], $contact);

        $this->assertStringStartsWith('Q-', $quote->number);
        $this->assertSame(2640.0, (float) $quote->subtotal);
        $this->assertSame(475.2, (float) $quote->tax_amount);
        $this->assertSame(3115.2, (float) $quote->total);
        $this->assertNotNull($quote->pdf_path);

        Storage::disk('local')->assertExists($quote->pdf_path);

        $pdf = Storage::disk('local')->get($quote->pdf_path);
        $this->assertStringStartsWith('%PDF-1.4', $pdf);
        $this->assertStringContainsString('COTIZACION '.$quote->number, $pdf);
    }

    public function test_create_quote_genera_numeros_secuenciales_por_tenant(): void
    {
        [$user, $tenant] = $this->authTenant('tools-quote-seq');
        $this->makeProducts();

        $a = Product::create(['title' => 'Servicio', 'price' => 100, 'currency' => 'USD', 'is_active' => true]);

        $q1 = app(QuoteService::class)->create($tenant, ['items' => [['title' => 'Servicio', 'quantity' => 1, 'amount' => 100]]]);
        $q2 = app(QuoteService::class)->create($tenant, ['items' => [['title' => 'Servicio', 'quantity' => 1, 'amount' => 100]]]);

        $this->assertSame('Q-tools-quote-seq-0001', $q1->number);
        $this->assertSame('Q-tools-quote-seq-0002', $q2->number);
    }

    public function test_create_lead_registra_lead_y_contacto(): void
    {
        [, $tenant] = $this->authTenant('tools-lead');
        $this->agentWithTools();

        $context = new ToolContext($tenant, $this->agentWithTools());
        $result = app(ToolManager::class)->run('create_lead', [
            'name' => 'Ana',
            'phone' => '+51999123456',
            'email' => 'ana@example.com',
            'intent' => 'cotizacion',
            'score' => 40,
        ], $context);

        $this->assertTrue($result['created']);
        $this->assertDatabaseHas('contacts', ['email' => 'ana@example.com']);
        $this->assertDatabaseHas('leads', ['intent' => 'cotizacion', 'lead_score' => 40]);
    }

    public function test_create_task_crea_una_tarea_de_seguimiento(): void
    {
        [, $tenant] = $this->authTenant('tools-task');
        $this->agentWithTools();

        $context = new ToolContext($tenant, $this->agentWithTools());
        $result = app(ToolManager::class)->run('create_task', [
            'title' => 'Llamar al cliente',
            'description' => 'Confirmar pedido',
        ], $context);

        $this->assertTrue($result['created']);
        $this->assertDatabaseHas('tasks', ['title' => 'Llamar al cliente', 'status' => 'open']);
    }

    public function test_n8n_webhook_encola_en_el_outbox(): void
    {
        [, $tenant] = $this->authTenant('tools-n8n');
        $this->agentWithTools();

        $context = new ToolContext($tenant, $this->agentWithTools());
        $result = app(ToolManager::class)->run('n8n_webhook', [
            'event' => 'quote.created',
            'payload' => ['number' => 'Q-001', 'total' => 100],
        ], $context);

        $this->assertTrue($result['queued']);
        $this->assertDatabaseHas('webhook_outbox', ['event' => 'quote.created', 'status' => 'pending']);
    }

    public function test_una_tool_no_habilitada_lanza_excepcion_y_no_ejecuta(): void
    {
        [, $tenant] = $this->authTenant('tools-disabled');
        $this->agentWithTools([]);

        $context = new ToolContext($tenant, $this->agentWithTools([]));

        $this->expectException(ToolException::class);
        app(ToolManager::class)->run('catalog_lookup', ['query' => 'x'], $context);
    }

    public function test_el_panel_puede_habilitar_herramientas_del_agente(): void
    {
        $this->authTenant('tools-update');
        $this->agentWithTools([]);

        $this->put('/app/tools', ['tools' => ['catalog_lookup', 'create_quote']])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(['catalog_lookup', 'create_quote'], $this->agentWithTools()->tools);
    }

    public function test_el_panel_rechaza_herramientas_desconocidas(): void
    {
        $this->authTenant('tools-invalid');
        $this->agentWithTools([]);

        $this->put('/app/tools', ['tools' => ['hack_tool']])->assertSessionHasErrors('tools.0');
    }

    public function test_el_panel_puede_anadir_un_producto_al_catalogo(): void
    {
        $this->authTenant('tools-add-product');

        $this->post('/app/tools/products', [
            'title' => 'Válvula industrial',
            'price' => 350,
            'currency' => 'USD',
            'category' => 'Válvulas',
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('products', ['title' => 'Válvula industrial', 'price' => 350.0]);
    }

    public function test_el_panel_puede_cambiar_el_estado_de_una_cotizacion(): void
    {
        [, $tenant] = $this->authTenant('tools-quote-status');
        $quote = app(QuoteService::class)->create($tenant, ['items' => [['title' => 'X', 'quantity' => 1, 'amount' => 10]]]);

        $this->patch("/app/tools/quotes/{$quote->id}", ['status' => 'accepted'])
            ->assertRedirect()->assertSessionHas('success');

        $this->assertSame('accepted', $quote->refresh()->status);
    }

    public function test_el_orquestador_ejecuta_el_flujo_de_cotizacion_en_el_chat(): void
    {
        $this->authTenant('tools-flow');
        $this->agentWithTools(['catalog_lookup', 'quote_calculator', 'create_quote', 'create_lead']);
        $this->makeProducts();

        $contact = Contact::create(['name' => 'Visitante']);
        $conversation = Conversation::create(['contact_id' => $contact->id, 'status' => 'open', 'channel' => 'web']);

        $orchestrator = app(ToolOrchestrator::class);

        $reply = $orchestrator->runQuoteFlow(
            tenant(),
            $this->agentWithTools(['catalog_lookup', 'quote_calculator', 'create_quote', 'create_lead']),
            $conversation,
            ['name' => 'Pedro', 'phone' => '+51999888777', 'email' => 'pedro@example.com'],
            'Quiero una cotización de una bomba'
        );

        $this->assertNotNull($reply);
        $this->assertStringContainsString('cotización', $reply);
        $this->assertStringContainsString('Bomba centrífuga HC', $reply);
        $this->assertDatabaseHas('quotes', ['status' => 'draft']);
        $this->assertDatabaseHas('leads', ['intent' => 'cotizacion']);
    }

    public function test_el_chat_con_intencion_de_cotizacion_responde_con_tools(): void
    {
        $this->authTenant('tools-chat');
        $this->agentWithTools(['catalog_lookup', 'quote_calculator', 'create_quote', 'create_lead']);
        $this->makeProducts();

        $response = $this->post('/api/chat/'.tenant()->slug, [
            'message' => 'Quiero cotizar una bomba centrífuga',
            'name' => 'Pedro',
            'phone' => '+51999888777',
        ]);

        $response->assertOk();
        $body = $response->streamedContent();

        $this->assertStringContainsString('"type":"done"', $body);
        $this->assertDatabaseHas('quotes', ['status' => 'draft']);
    }

    public function test_el_chat_sin_tools_habilitadas_no_cotiza(): void
    {
        $this->authTenant('tools-chat-disabled');
        $this->agentWithTools([]);
        $this->makeProducts();

        $response = $this->post('/api/chat/'.tenant()->slug, [
            'message' => 'Quiero cotizar una bomba',
        ]);

        $response->assertOk();

        $this->assertDatabaseMissing('quotes', ['status' => 'draft']);
    }

    public function test_el_plan_limita_las_tools_permitidas(): void
    {
        $this->authTenant('tools-plan-starter', 'starter');
        $allowed = app(PlanService::class)->toolsAllowed(tenant());

        $this->assertSame(['catalog_lookup', 'quote_calculator', 'create_quote'], $allowed);
        $this->assertNotContains('create_lead', $allowed);
        $this->assertNotContains('n8n_webhook', $allowed);

        $this->authTenant('tools-plan-pro', 'pro');
        $this->assertContains('n8n_webhook', app(PlanService::class)->toolsAllowed(tenant()));
    }

    public function test_una_tool_fuera_del_plan_no_ejecuta_aunque_este_habilitada_en_el_agente(): void
    {
        $this->authTenant('tools-plan-limit', 'starter');
        $this->agentWithTools(['create_lead']);

        $context = new ToolContext(tenant(), $this->agentWithTools(['create_lead']));

        $this->expectException(ToolException::class);
        app(ToolManager::class)->run('create_lead', ['name' => 'Ana', 'phone' => '+51999123456'], $context);
    }

    public function test_el_panel_rechaza_tools_fuera_del_plan(): void
    {
        $this->authTenant('tools-plan-panel', 'starter');
        $this->agentWithTools([]);

        $this->put('/app/tools', ['tools' => ['n8n_webhook']])->assertSessionHasErrors('tools.0');
    }

    public function test_el_panel_muestra_las_tools_permitidas_por_plan(): void
    {
        $this->authTenant('tools-plan-page', 'starter');
        $this->agentWithTools(['catalog_lookup']);

        $this->get('/app/tools')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Tools')
                ->where('plan', 'Starter')
                ->has('allowed', 3)
                ->where('allowed.0', 'catalog_lookup')
                ->where('allowed.2', 'create_quote'));
    }

    public function test_la_web_publica_expone_las_capacidades_activas_del_agente(): void
    {
        $this->authTenant('tools-web-caps', 'pro');
        $this->agentWithTools(['catalog_lookup', 'create_quote', 'n8n_webhook']);
        app(WebsiteBuilderService::class)->createSite(tenant(), 'minimal-business', 'Tools Demo');

        $this->get('/site/'.tenant()->slug)
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('PublicSite')
                ->where('site.chat.capabilities.0', 'Consultar el catálogo de productos')
                ->where('site.chat.capabilities.1', 'Generar cotizaciones con PDF'));
    }

    public function test_la_web_publica_no_expone_capacidades_si_no_hay_tools_activas(): void
    {
        $this->authTenant('tools-web-nocaps', 'starter');
        $this->agentWithTools([]);
        app(WebsiteBuilderService::class)->createSite(tenant(), 'minimal-business', 'Tools Demo');

        $this->get('/site/'.tenant()->slug)
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('PublicSite')
                ->where('site.chat.capabilities', []));
    }
}
