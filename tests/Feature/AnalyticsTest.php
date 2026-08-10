<?php

namespace Tests\Feature;

use App\Models\AnalyticsEvent;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Lead;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_pagina_de_analitica_entrega_totales_y_tendencia(): void
    {
        $user = User::factory()->create();
        $tenant = $this->makeTenant('Analítica Demo', 'analytics-demo');
        $tenant->users()->attach($user->id, ['role' => 'owner']);
        $this->switchTenant($tenant);
        $this->actingAs($user)->withSession(['current_tenant_id' => $tenant->id]);

        $contact = Contact::create(['name' => 'Ana', 'email' => 'ana@example.com']);
        Conversation::create(['contact_id' => $contact->id, 'status' => 'open', 'channel' => 'web']);
        Conversation::create(['contact_id' => $contact->id, 'status' => 'open', 'channel' => 'whatsapp']);
        Lead::create(['contact_id' => $contact->id, 'source_channel' => 'web', 'status' => 'new']);
        Lead::create(['contact_id' => $contact->id, 'source_channel' => 'whatsapp', 'status' => 'qualified']);
        Message::create(['conversation_id' => Conversation::first()->id, 'direction' => 'in', 'author_type' => 'visitor', 'content' => 'hola']);
        AnalyticsEvent::create(['kind' => 'unanswered_question', 'context' => ['question' => '¿Precio?', 'conversation_id' => null]]);

        $this->get('/app/analytics')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Analytics')
                ->where('totals.conversations', 2)
                ->where('totals.leads', 2)
                ->where('totals.messages', 1)
                ->where('totals.unanswered_total', 1)
                ->has('by_channel', 2)
                ->has('leads_by_status', 2)
                ->has('leads_by_source', 2)
                ->has('trend', 14));
    }

    public function test_la_tendencia_suma_los_eventos_del_dia(): void
    {
        $user = User::factory()->create();
        $tenant = $this->makeTenant('Analítica Trend', 'analytics-trend');
        $tenant->users()->attach($user->id, ['role' => 'owner']);
        $this->switchTenant($tenant);
        $this->actingAs($user)->withSession(['current_tenant_id' => $tenant->id]);

        $contact = Contact::create(['name' => 'Leo']);
        Conversation::create(['contact_id' => $contact->id, 'status' => 'open', 'channel' => 'web']);
        Conversation::create(['contact_id' => $contact->id, 'status' => 'open', 'channel' => 'web']);

        $response = $this->get('/app/analytics');

        $response->assertInertia(fn ($page) => $page
            ->component('Analytics')
            ->where('trend.13.conversations', 2));
    }

    public function test_la_analitica_no_filtra_datos_de_otros_tenants(): void
    {
        $user = User::factory()->create();
        $tenant = $this->makeTenant('Analítica A', 'analytics-a');
        $tenant->users()->attach($user->id, ['role' => 'owner']);
        $this->switchTenant($tenant);
        $this->actingAs($user)->withSession(['current_tenant_id' => $tenant->id]);

        $other = $this->makeTenant('Analítica B', 'analytics-b');
        $this->switchTenant($other);
        $contact = Contact::create(['name' => 'Beto']);
        Conversation::create(['contact_id' => $contact->id, 'status' => 'open', 'channel' => 'whatsapp']);
        Lead::create(['contact_id' => $contact->id, 'source_channel' => 'whatsapp']);

        $this->switchTenant($tenant);

        $this->get('/app/analytics')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Analytics')
                ->where('totals.conversations', 0)
                ->where('totals.leads', 0)
                ->has('by_channel', 0));
    }

    public function test_el_rango_de_dias_es_respetado(): void
    {
        $user = User::factory()->create();
        $tenant = $this->makeTenant('Analítica Range', 'analytics-range');
        $tenant->users()->attach($user->id, ['role' => 'owner']);
        $this->switchTenant($tenant);
        $this->actingAs($user)->withSession(['current_tenant_id' => $tenant->id]);

        $this->get('/app/analytics?days=30')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Analytics')
                ->where('range_days', 30)
                ->has('trend', 30));
    }
}
