<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\AnalyticsEvent;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Lead;
use App\Models\Message;
use App\Services\Chat\ChatService;
use App\Services\Knowledge\KnowledgePipelineService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_chat_responde_y_registra_conversacion_y_lead(): void
    {
        $tenant = $this->makeTenant('Empresa Chat', 'chat-empresa');
        $this->switchTenant($tenant);

        $tenant->profile()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Empresa Chat',
            'description' => 'Servicios de plomería y mantenimiento.',
            'contact' => ['phone' => '+56911111111'],
        ]);

        Agent::create([
            'tenant_id' => $tenant->id,
            'slug' => 'assistant',
            'name' => 'Asistente',
            'tools' => [],
            'is_active' => true,
        ]);

        $document = app(KnowledgePipelineService::class)->createFromText(
            $tenant,
            'Info',
            'Horario de la empresa: lunes a viernes de 9 a 18 horas.'
        );
        app(KnowledgePipelineService::class)->process($document);

        $result = app(ChatService::class)->respond($tenant->slug, '¿Cuál es su horario?', [
            'name' => 'Juan',
            'email' => 'juan@example.com',
        ]);

        $this->assertNotEmpty($result['reply']);
        $this->assertTrue($result['conversation_id'] !== null);

        $this->assertSame(2, Message::count());
        $this->assertSame(1, Conversation::count());
        $this->assertSame('web', Conversation::first()->channel);
        $this->assertSame(1, Lead::count());
        $this->assertSame(1, AnalyticsEvent::where('kind', 'chat_message')->count());
        $this->assertSame(1, AnalyticsEvent::where('kind', 'lead_generated')->count());
    }

    public function test_el_chat_reutiliza_contacto_y_conversacion_existente(): void
    {
        $tenant = $this->makeTenant('Empresa Chat 2', 'chat-empresa-2');
        $this->switchTenant($tenant);

        app(ChatService::class)->respond($tenant->slug, 'Hola', ['email' => 'repite@example.com']);
        app(ChatService::class)->respond($tenant->slug, 'Hola de nuevo', ['email' => 'repite@example.com']);

        $this->assertSame(1, Contact::count());
        $this->assertSame(1, Conversation::count());
        $this->assertSame(4, Message::count());
    }

    public function test_el_chat_no_inventa_datos_que_no_estan_en_el_conocimiento(): void
    {
        $tenant = $this->makeTenant('Empresa Sin Ubicacion', 'empresa-sin-ubicacion');
        $this->switchTenant($tenant);

        $result = app(ChatService::class)->respond($tenant->slug, '¿Dónde están ubicados?');

        $this->assertSame(
            'No tengo ese dato disponible en este momento. Puedes dejarnos tu consulta en el formulario de contacto y un asesor te ayudará.',
            $result['reply']
        );
        $this->assertStringNotContainsString('Valencia', $result['reply']);
        $this->assertSame([], $result['sources']);
    }

    public function test_el_chat_rechaza_cambio_de_idioma_de_forma_consistente(): void
    {
        $tenant = $this->makeTenant('Empresa Idiomas', 'chat-idiomas');
        $this->switchTenant($tenant);
        $chunks = [];

        $result = app(ChatService::class)->respond(
            $tenant->slug,
            '¿Puedes responder en portugués?',
            [],
            function ($chunk) use (&$chunks) {
                $chunks[] = $chunk;
            }
        );

        $this->assertSame(
            'Por ahora solo puedo responder en español. Puedes escribirme tu consulta en español y con gusto te ayudaré.',
            $result['reply']
        );
        $this->assertSame($result['reply'], implode('', $chunks));
        $this->assertSame(2, Message::count());
    }
}
