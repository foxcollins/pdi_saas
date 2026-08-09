<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Lead;
use App\Models\Note;
use App\Models\Task;
use App\Models\User;
use App\Services\Chat\ChatService;
use App\Services\Knowledge\KnowledgePipelineService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrmTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_chat_sin_respuesta_escala_la_conversacion(): void
    {
        $tenant = $this->makeTenant('CRM Escala', 'crm-escala');
        $this->switchTenant($tenant);

        app(ChatService::class)->respond($tenant->slug, '¿Hacen instalaciones en Antofagasta?');

        $conversation = Conversation::first();

        $this->assertNotNull($conversation);
        $this->assertTrue($conversation->needs_human);
        $this->assertNotNull($conversation->escalated_at);
    }

    public function test_el_chat_con_respuesta_no_escala_la_conversacion(): void
    {
        $tenant = $this->makeTenant('CRM No Escala', 'crm-no-escala');
        $this->switchTenant($tenant);

        $pipeline = app(KnowledgePipelineService::class);
        $doc = $pipeline->createFromText(
            $tenant,
            'Info',
            'Horario de atención: lunes a viernes de 9 a 18 horas y sábados de 10 a 14.'
        );
        $pipeline->process($doc);

        app(ChatService::class)->respond($tenant->slug, '¿Cuál es el horario de atención?');

        $this->assertFalse(Conversation::first()->needs_human);
    }

    public function test_las_notas_y_tareas_se_aislan_por_tenant(): void
    {
        $a = $this->makeTenant('CRM A', 'crm-a');
        $b = $this->makeTenant('CRM B', 'crm-b');

        $this->switchTenant($a);
        $contactA = Contact::create(['name' => 'Contacto A']);
        Note::create(['contact_id' => $contactA->id, 'body' => 'Nota privada A']);
        Task::create(['contact_id' => $contactA->id, 'title' => 'Tarea A']);

        $this->switchTenant($b);
        $this->assertSame(0, Note::count());
        $this->assertSame(0, Task::count());

        $this->switchTenant($a);
        $this->assertSame(1, Note::count());
        $this->assertSame(1, Task::count());
    }

    public function test_el_crm_index_muestra_bandeja_pipeline_contactos_y_tareas(): void
    {
        $user = User::factory()->create();
        $tenant = $this->makeTenant('CRM Index', 'crm-index');
        $tenant->users()->attach($user->id, ['role' => 'owner']);
        $this->switchTenant($tenant);
        $this->actingAs($user)->withSession(['current_tenant_id' => $tenant->id]);

        $contact = Contact::create(['name' => 'Ana', 'email' => 'ana@example.com']);
        Lead::create([
            'contact_id' => $contact->id,
            'source_channel' => 'web',
            'intent' => 'cotización',
            'lead_score' => 85,
            'status' => 'qualified',
        ]);
        Conversation::create([
            'contact_id' => $contact->id,
            'channel' => 'web',
            'status' => 'open',
            'needs_human' => true,
            'escalated_at' => now(),
        ]);
        Task::create(['contact_id' => $contact->id, 'title' => 'Llamar a Ana']);
        Note::create(['contact_id' => $contact->id, 'body' => 'Prefiere ser contactada por teléfono.']);

        $this->get('/app/crm')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Crm')
                ->has('contacts', 1)
                ->has('inbox', 1)
                ->has('tasks', 1)
                ->has('notes', 1)
                ->where('pipeline.qualified.0.contact', 'Ana'));
    }
}
