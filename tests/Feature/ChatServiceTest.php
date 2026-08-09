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
}
