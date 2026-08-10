<?php

namespace Tests\Feature;

use App\Models\Integration;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ChannelsTest extends TestCase
{
    use RefreshDatabase;

    private function authTenant(string $slug = 'integrations-demo'): array
    {
        $user = User::factory()->create();
        $tenant = $this->makeTenant('Integrations Demo', $slug);
        $tenant->users()->attach($user->id, ['role' => 'owner']);
        $this->switchTenant($tenant);
        $this->actingAs($user)->withSession(['current_tenant_id' => $tenant->id]);

        return [$user, $tenant];
    }

    private function configureChannel(array $data, string $channel = 'whatsapp'): Integration
    {
        return Integration::create([
            'tenant_id' => tenant()->id,
            'channel' => $channel,
            'provider' => 'meta',
            'config_encrypted' => Crypt::encryptString(json_encode($data)),
            'status' => 'active',
            'webhook_secret' => 'test-secret-'.str_repeat('x', 40),
        ]);
    }

    public function test_el_panel_entrega_los_canales_disponibles(): void
    {
        $this->authTenant('integrations-panel');

        $this->get('/app/integrations')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Integrations')
                ->has('channels', 5)
                ->where('channels.0.label', 'WhatsApp')
                ->where('channels.4.label', 'Email'));
    }

    public function test_configurar_un_canal_guarda_las_credenciales_cifradas(): void
    {
        $this->authTenant('integrations-store');

        $this->post('/app/integrations/whatsapp', [
            'phone_number_id' => '123456789',
            'access_token' => 'EAA-secret-token',
            'waba_id' => '987654',
        ])->assertRedirect()->assertSessionHas('success');

        $integration = Integration::where('channel', 'whatsapp')->first();

        $this->assertNotNull($integration);
        $this->assertSame('active', $integration->status);
        $this->assertNotEmpty($integration->webhook_secret);

        $decrypted = json_decode(Crypt::decryptString($integration->config_encrypted), true);

        $this->assertSame('123456789', $decrypted['phone_number_id']);
        $this->assertStringNotContainsString('EAA-secret-token', $integration->getAttributes()['config_encrypted'] ?? '');
    }

    public function test_el_verify_de_meta_devuelve_el_challenge_con_token_valido(): void
    {
        [, $tenant] = $this->authTenant('integrations-verify');
        $this->configureChannel(['phone_number_id' => '111'], 'whatsapp');

        $this->get("/webhooks/whatsapp/{$tenant->slug}/verify?hub_mode=subscribe&hub_verify_token=test-secret-{$this->secretTail()}&hub_challenge=CHALLENGE123")
            ->assertOk()
            ->assertSee('CHALLENGE123', false);
    }

    public function test_el_verify_rechaza_un_token_invalido(): void
    {
        [, $tenant] = $this->authTenant('integrations-verify-bad');
        $this->configureChannel(['phone_number_id' => '111'], 'whatsapp');

        $this->get("/webhooks/whatsapp/{$tenant->slug}/verify?hub_mode=subscribe&hub_verify_token=wrong&hub_challenge=CHALLENGE123")
            ->assertStatus(403);
    }

    public function test_el_webhook_entrante_con_firma_valida_procesa_y_envia_respuesta(): void
    {
        [, $tenant] = $this->authTenant('integrations-inbound');
        $integration = $this->configureChannel(['phone_number_id' => '222'], 'whatsapp');

        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid-1']]], 200),
        ]);

        $payload = [
            'entry' => [[
                'changes' => [[
                    'value' => [
                        'contacts' => [['wa_id' => '5491112223333', 'profile' => ['name' => 'Juan']]],
                        'messages' => [['from' => '5491112223333', 'type' => 'text', 'text' => ['body' => '¿Qué servicios ofrecen?']]],
                    ],
                ]],
            ]],
        ];

        $body = json_encode($payload);
        $signature = 'sha256='.hash_hmac('sha256', $body, $integration->webhook_secret);

        $response = $this->postJson("/webhooks/whatsapp/{$tenant->slug}", $payload, [
            'X-Hub-Signature-256' => $signature,
        ]);

        $response->assertOk()->assertJson(['status' => 'ok']);

        $this->assertDatabaseHas('messages', [
            'direction' => 'in',
            'content' => '¿Qué servicios ofrecen?',
        ]);

        $integration->refresh();
        $this->assertNotNull($integration->last_sync_at);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/messages')
                && str_contains($request->body(), '5491112223333');
        });
    }

    public function test_el_webhook_rechaza_una_firma_invalida(): void
    {
        [, $tenant] = $this->authTenant('integrations-badsig');
        $this->configureChannel(['phone_number_id' => '333'], 'whatsapp');

        $payload = ['entry' => [['changes' => [['value' => [
            'contacts' => [['wa_id' => '5491112223333']],
            'messages' => [['from' => '5491112223333', 'type' => 'text', 'text' => ['body' => 'Hola']]],
        ]]]]]];

        $this->postJson("/webhooks/whatsapp/{$tenant->slug}", $payload, [
            'X-Hub-Signature-256' => 'sha256=invalid',
        ])->assertStatus(403);

        $this->assertDatabaseMissing('messages', ['content' => 'Hola']);
    }

    public function test_el_webhook_no_filtra_datos_de_otro_tenant(): void
    {
        [, $tenantA] = $this->authTenant('integrations-tenant-a');
        $this->configureChannel(['phone_number_id' => '444'], 'whatsapp');

        $tenantB = $this->makeTenant('Otra Empresa', 'integrations-tenant-b');
        $this->switchTenant($tenantB);

        $this->postJson("/webhooks/whatsapp/{$tenantB->slug}", [
            'entry' => [['changes' => [['value' => [
                'contacts' => [['wa_id' => '5491112223333']],
                'messages' => [['from' => '5491112223333', 'type' => 'text', 'text' => ['body' => 'Hola']]],
            ]]]]],
        ])->assertStatus(403);
    }

    public function test_telegram_webhook_procesa_un_mensaje_del_bot(): void
    {
        [, $tenant] = $this->authTenant('integrations-telegram');
        $integration = $this->configureChannel(['bot_token' => '123:ABC-token', 'bot_username' => 'mi_bot'], 'telegram');

        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 42]], 200),
        ]);

        $response = $this->postJson("/webhooks/telegram/{$tenant->slug}", [
            'update_id' => 1,
            'message' => [
                'chat' => ['id' => 777, 'first_name' => 'Pedro'],
                'from' => ['username' => 'pedro'],
                'text' => 'Hola bot',
            ],
        ]);

        $response->assertOk()->assertJson(['status' => 'ok']);

        $this->assertDatabaseHas('messages', [
            'direction' => 'in',
            'content' => 'Hola bot',
        ]);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/sendMessage')
                && str_contains($request->body(), 'chat_id')
                && str_contains($request->body(), '777');
        });

        $integration->refresh();
        $this->assertNotNull($integration->last_sync_at);
    }

    public function test_el_webhook_es_idempotente_para_el_mismo_mensaje(): void
    {
        [, $tenant] = $this->authTenant('integrations-idem');
        $integration = $this->configureChannel(['phone_number_id' => '555'], 'whatsapp');

        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid-2']]], 200),
        ]);

        $payload = [
            'entry' => [[
                'changes' => [[
                    'value' => [
                        'contacts' => [['wa_id' => '5491112223333']],
                        'messages' => [['from' => '5491112223333', 'type' => 'text', 'text' => ['body' => 'Mismo mensaje']]],
                    ],
                ]],
            ]],
        ];

        $body = json_encode($payload);
        $signature = 'sha256='.hash_hmac('sha256', $body, $integration->webhook_secret);

        $this->postJson("/webhooks/whatsapp/{$tenant->slug}", $payload, ['X-Hub-Signature-256' => $signature])->assertOk();
        $this->postJson("/webhooks/whatsapp/{$tenant->slug}", $payload, ['X-Hub-Signature-256' => $signature])->assertOk();

        $this->assertSame(1, Message::where('content', 'Mismo mensaje')->count());
    }

    private function secretTail(): string
    {
        return str_repeat('x', 40);
    }
}
