<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgentTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_tenant_puede_configurar_su_asistente(): void
    {
        $user = User::factory()->create();
        $tenant = $this->makeTenant('Agente Demo', 'agente-demo');
        $tenant->users()->attach($user->id, ['role' => 'owner']);
        $this->switchTenant($tenant);
        $this->actingAs($user)->withSession(['current_tenant_id' => $tenant->id]);

        $this->get('/app/assistant')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Agent'));

        $this->put('/app/assistant', [
            'name' => 'Sofía',
            'instructions' => 'Prioriza detectar oportunidades comerciales.',
            'tone' => 'comercial y persuasivo',
            'language' => 'español',
            'welcome' => 'Hola, soy Sofía. ¿En qué te ayudo?',
            'escalation' => 'Deriva las cotizaciones especiales a un asesor.',
            'is_active' => true,
        ])->assertRedirect();

        $agent = Agent::query()->where('slug', 'assistant')->firstOrFail();

        $this->assertSame('Sofía', $agent->name);
        $this->assertSame('comercial y persuasivo', $agent->guardrails['tone']);
        $this->assertSame('Hola, soy Sofía. ¿En qué te ayudo?', $agent->guardrails['welcome']);
    }
}
