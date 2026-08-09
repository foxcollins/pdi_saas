<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Conversation;
use App\Models\CustomerMemory;
use App\Models\Message;
use App\Models\User;
use App\Services\Chat\ChatService;
use App\Services\Memory\MemoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_chat_captura_preferencias_e_intereses_del_cliente(): void
    {
        $tenant = $this->makeTenant('Memoria Chat', 'mem-chat');
        $this->switchTenant($tenant);

        app(ChatService::class)->respond($tenant->slug, 'Busco una bomba hidr\u00e1ulica de alta presi\u00f3n', [
            'name' => 'Cliente',
            'email' => 'cliente@example.com',
        ]);

        $contact = Contact::first();
        $this->assertNotNull($contact);

        $this->assertGreaterThan(0, CustomerMemory::where('contact_id', $contact->id)->count());
        $this->assertNotEmpty($contact->memory_summary['interests'] ?? []);
        $this->assertStringContainsString('bomba hidr\u00e1ulica', $contact->memory_summary['interests'][0] ?? '');
    }

    public function test_el_consentimiento_se_actualiza_y_respeta(): void
    {
        $tenant = $this->makeTenant('Memoria Consent', 'mem-consent');
        $this->switchTenant($tenant);

        $contact = Contact::create(['name' => 'Ana', 'consent_status' => 'unknown']);
        app(MemoryService::class)->setConsent($contact, 'granted');

        $this->assertSame('granted', $contact->fresh()->consent_status);

        app(MemoryService::class)->setConsent($contact, 'withdrawn');
        $this->assertSame('withdrawn', $contact->fresh()->consent_status);
    }

    public function test_olvidar_anonimiza_y_elimina_memoria_y_conversaciones(): void
    {
        $tenant = $this->makeTenant('Memoria Forget', 'mem-forget');
        $this->switchTenant($tenant);

        $contact = Contact::create([
            'name' => 'Juan',
            'email' => 'juan@example.com',
            'phone' => '+56911111111',
            'consent_status' => 'granted',
        ]);
        $conversation = Conversation::create([
            'contact_id' => $contact->id,
            'channel' => 'web',
            'status' => 'open',
        ]);
        Message::create([
            'conversation_id' => $conversation->id,
            'direction' => 'in',
            'author_type' => 'visitor',
            'content' => 'Busco instalaci\u00f3n de riego',
        ]);
        CustomerMemory::create([
            'contact_id' => $contact->id,
            'kind' => 'summary',
            'content' => ['interests' => ['riego']],
            'policy' => 'retain_365',
        ]);

        app(MemoryService::class)->forget($contact, true);

        $contact->refresh();
        $this->assertSame('revoked', $contact->consent_status);
        $this->assertNull($contact->email);
        $this->assertNull($contact->phone);
        $this->assertNotNull($contact->anonymized_at);
        $this->assertSame(0, CustomerMemory::count());
        $this->assertSame(0, Conversation::count());
        $this->assertSame(0, Message::count());
    }

    public function test_la_retencion_elimina_memoria_expirada_solo_del_tenant(): void
    {
        $a = $this->makeTenant('Mem Ret A', 'mem-ret-a');
        $b = $this->makeTenant('Mem Ret B', 'mem-ret-b');

        $this->switchTenant($a);
        $ca = Contact::create(['name' => 'A']);
        CustomerMemory::create([
            'contact_id' => $ca->id,
            'kind' => 'summary',
            'content' => ['interests' => ['x']],
            'created_at' => now()->subDays(400),
            'window_end' => now()->subDays(400),
        ]);

        $this->switchTenant($b);
        $cb = Contact::create(['name' => 'B']);
        CustomerMemory::create([
            'contact_id' => $cb->id,
            'kind' => 'summary',
            'content' => ['interests' => ['y']],
            'created_at' => now()->subDays(10),
            'window_end' => now()->addDays(10),
        ]);

        $this->switchTenant($a);
        $pruned = app(MemoryService::class)->pruneExpired(365);

        $this->assertSame(1, $pruned);
        $this->assertSame(0, CustomerMemory::count());

        $this->switchTenant($b);
        $this->assertSame(1, CustomerMemory::count());
    }

    public function test_la_pagina_de_memoria_muestra_contactos_y_politica(): void
    {
        $user = User::factory()->create();
        $tenant = $this->makeTenant('Mem Index', 'mem-index');
        $tenant->users()->attach($user->id, ['role' => 'owner']);
        $this->switchTenant($tenant);
        $this->actingAs($user)->withSession(['current_tenant_id' => $tenant->id]);

        $contact = Contact::create(['name' => 'Luis', 'consent_status' => 'granted']);
        CustomerMemory::create([
            'contact_id' => $contact->id,
            'kind' => 'summary',
            'content' => ['preferences' => ['contacto por tel\u00e9fono'], 'interests' => ['riego']],
            'policy' => 'retain_365',
        ]);
        app(MemoryService::class)->consolidate($contact);

        $this->get('/app/memory')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Memory')
                ->has('contacts', 1)
                ->where('retention_days', 365)
                ->where('contacts.0.memory_summary.interests.0', 'riego'));
    }
}
