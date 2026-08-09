<?php

namespace Tests\Feature;

use App\Models\AnalyticsEvent;
use App\Models\Conversation;
use App\Models\Lead;
use App\Models\Message;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_formulario_de_contacto_crea_lead_y_conversacion(): void
    {
        $tenant = $this->makeTenant('Contacto Co', 'contacto-co');

        $this->postJson('/api/contact/'.$tenant->slug, [
            'name' => 'Ana',
            'email' => 'ana@example.com',
            'phone' => '+56922222222',
            'message' => 'Quiero cotizar un servicio.',
        ])->assertOk()->assertJson(['ok' => true]);

        $this->assertSame(1, Message::count());
        $this->assertSame(1, Conversation::count());
        $this->assertSame(1, Lead::count());
        $this->assertSame(1, AnalyticsEvent::where('kind', 'lead_generated')->count());
    }

    public function test_el_formulario_requiere_un_medio_de_contacto(): void
    {
        $tenant = $this->makeTenant('Contacto Co 2', 'contacto-co2');

        $this->postJson('/api/contact/'.$tenant->slug, [
            'message' => 'Mensaje sin datos de contacto.',
        ])->assertStatus(422);
    }

    public function test_devuelve_404_si_el_tenant_no_existe(): void
    {
        $this->postJson('/api/contact/tenant-que-no-existe', [
            'name' => 'X',
            'message' => 'Hola',
        ])->assertStatus(404);
    }
}
