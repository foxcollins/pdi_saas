<?php

namespace Tests\Feature;

use App\Models\AnalyticsEvent;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Lead;
use App\Models\Message;
use App\Models\User;
use App\Services\Chat\ChatService;
use App\Services\Knowledge\KnowledgePipelineService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardMetricsTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_chat_sin_conocimiento_registra_pregunta_sin_respuesta(): void
    {
        $tenant = $this->makeTenant('Empresa Sin KB', 'sin-kb');
        $this->switchTenant($tenant);

        app(ChatService::class)->respond($tenant->slug, '¿Cuánto cuesta una bomba de agua marca XYZ?');

        $this->assertSame(1, AnalyticsEvent::where('kind', 'unanswered_question')->count());

        $event = AnalyticsEvent::where('kind', 'unanswered_question')->first();
        $this->assertArrayHasKey('question', $event->context);
        $this->assertStringContainsString('bomba de agua', $event->context['question']);
    }

    public function test_el_chat_con_conocimiento_no_registra_pregunta_sin_respuesta(): void
    {
        $tenant = $this->makeTenant('Empresa Con KB', 'con-kb');
        $this->switchTenant($tenant);

        $pipeline = app(KnowledgePipelineService::class);
        $doc = $pipeline->createFromText(
            $tenant,
            'Info',
            'Horario de atención: lunes a viernes de 9 a 18 horas y sábados de 10 a 14.'
        );
        $pipeline->process($doc);

        app(ChatService::class)->respond($tenant->slug, '¿Cuál es el horario de atención?');

        $this->assertSame(0, AnalyticsEvent::where('kind', 'unanswered_question')->count());
    }

    public function test_el_dashboard_entrega_metricas_de_preguntas_sin_respuesta(): void
    {
        $user = User::factory()->create();
        $tenant = $this->makeTenant('Dashboard Metrics', 'dash-metrics');
        $tenant->users()->attach($user->id, ['role' => 'owner']);
        $this->switchTenant($tenant);
        $this->actingAs($user)->withSession(['current_tenant_id' => $tenant->id]);

        AnalyticsEvent::create([
            'kind' => 'unanswered_question',
            'context' => ['question' => '¿Precio de instalación?', 'conversation_id' => null],
        ]);
        AnalyticsEvent::create([
            'kind' => 'unanswered_question',
            'context' => ['question' => '¿Hacen envíos a regiones?', 'conversation_id' => null],
        ]);

        Conversation::create(['contact_id' => Contact::create(['name' => 'C'])->id, 'status' => 'open', 'channel' => 'web']);
        Lead::create(['contact_id' => Contact::first()->id, 'source_channel' => 'web']);
        Message::create(['conversation_id' => Conversation::first()->id, 'direction' => 'in', 'author_type' => 'visitor', 'content' => 'hola']);

        $response = $this->get('/app/dashboard')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Dashboard')
                ->where('metrics.unanswered_total', 2)
                ->has('unanswered_questions', 2));
    }
}
