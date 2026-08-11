<?php

namespace App\Services\Agents;

use App\Models\Agent;
use App\Models\Tenant;

class AgentPresetService
{
    public function presets(): array
    {
        return [
            [
                'slug' => 'assistant',
                'name' => 'Asistente general',
                'description' => 'Primer contacto y respuestas generales sobre el negocio.',
                'instructions' => 'Responde con la información autorizada del negocio.',
                'trigger_keywords' => [],
                'tools' => [],
                'is_active' => true,
                'guardrails' => [
                    'tone' => 'profesional y cercano',
                    'language' => 'español',
                    'welcome' => 'Hola, ¿en qué puedo ayudarte?',
                    'escalation' => 'Cuando no tengas información suficiente, deriva a un asesor humano.',
                ],
            ],
            [
                'slug' => 'reception',
                'name' => 'Recepción',
                'description' => 'Saluda, identifica al visitante y responde preguntas generales.',
                'instructions' => 'Recibe al visitante, preséntate, identifica su necesidad y ofrécele orientación general. Deriva a un agente especializado o humano cuando corresponda.',
                'trigger_keywords' => ['hola', 'buenos días', 'buenas tardes', 'quién eres', 'quiénes son', 'información', 'contacto'],
                'tools' => [],
                'is_active' => false,
            ],
            [
                'slug' => 'sales',
                'name' => 'Ventas',
                'description' => 'Vende y cotiza: consulta el catálogo, calcula presupuestos y genera cotizaciones.',
                'instructions' => 'Eres el agente de ventas. Ayuda a cotizar productos del catálogo, calcula precios y ofrece generar una cotización formal con PDF.',
                'trigger_keywords' => ['precio', 'cotiz', 'comprar', 'cuánto', 'cuanto', 'cuesta', 'vale', 'producto', 'vender', 'oferta', 'presupuest', 'adquirir'],
                'tools' => ['catalog_lookup', 'quote_calculator', 'create_quote', 'create_lead'],
                'is_active' => false,
            ],
            [
                'slug' => 'support',
                'name' => 'Soporte',
                'description' => 'Resuelve dudas postventa, garantías y problemas técnicos.',
                'instructions' => 'Eres el agente de soporte. Ayuda con problemas, garantías y dudas postventa. Escala a un humano cuando el caso lo requiera.',
                'trigger_keywords' => ['problema', 'no funciona', 'no sirve', 'garantía', 'garantia', 'falla', 'error', 'devolución', 'devolucion', 'reclamo', 'reparación', 'reparacion'],
                'tools' => ['create_task', 'notify_human'],
                'is_active' => false,
            ],
            [
                'slug' => 'booking',
                'name' => 'Agenda',
                'description' => 'Gestiona citas y reservas.',
                'instructions' => 'Eres el agente de agenda. Ayuda a agendar citas y reservas, consulta disponibilidad y confirma los datos del cliente.',
                'trigger_keywords' => ['cita', 'reservar', 'reserva', 'agendar', 'agenda', 'turno', 'disponibilidad', 'horario'],
                'tools' => ['create_task'],
                'is_active' => false,
            ],
            [
                'slug' => 'followup',
                'name' => 'Seguimiento',
                'description' => 'Persigue leads sin respuesta y clientes recientes.',
                'instructions' => 'Eres el agente de seguimiento. Da seguimiento a leads sin respuesta y clientes recientes, creando tareas de contacto.',
                'trigger_keywords' => [],
                'tools' => ['create_task'],
                'is_active' => false,
            ],
        ];
    }

    public function ensureForTenant(Tenant $tenant): void
    {
        $existing = Agent::query()
            ->where('tenant_id', $tenant->id)
            ->pluck('slug')
            ->all();

        $defaults = $this->presets();

        $missing = collect($defaults)
            ->reject(fn ($preset) => in_array($preset['slug'], $existing, true))
            ->values();

        foreach ($missing as $preset) {
            Agent::create([
                'tenant_id' => $tenant->id,
                'slug' => $preset['slug'],
                'name' => $preset['name'],
                'description' => $preset['description'],
                'instructions' => $preset['instructions'],
                'trigger_keywords' => $preset['trigger_keywords'],
                'tools' => $preset['tools'],
                'is_active' => $preset['is_active'],
                'guardrails' => $preset['guardrails'] ?? [],
            ]);
        }
    }
}
