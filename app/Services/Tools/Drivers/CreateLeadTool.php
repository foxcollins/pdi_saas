<?php

namespace App\Services\Tools\Drivers;

use App\Models\AnalyticsEvent;
use App\Models\Contact;
use App\Models\Lead;
use App\Services\Tools\BaseTool;
use App\Services\Tools\ToolContext;

class CreateLeadTool extends BaseTool
{
    public function name(): string
    {
        return 'create_lead';
    }

    public function description(): string
    {
        return 'Registra un lead en el CRM con intención y score. Usado cuando el visitante muestra interés de compra o cotización.';
    }

    public function permission(): string
    {
        return 'internal';
    }

    public function parameters(): array
    {
        return [
            'name' => ['type' => 'string', 'required' => false, 'description' => 'Nombre del contacto.'],
            'phone' => ['type' => 'string', 'required' => false, 'description' => 'Teléfono.'],
            'email' => ['type' => 'string', 'required' => false, 'description' => 'Email.'],
            'intent' => ['type' => 'string', 'required' => false, 'description' => 'Intención detectada (p. ej. cotizacion, compra).'],
            'score' => ['type' => 'integer', 'required' => false, 'description' => 'Score del lead (0-100).'],
        ];
    }

    public function execute(array $args, ToolContext $context): array
    {
        $email = trim((string) ($args['email'] ?? ''));
        $phone = trim((string) ($args['phone'] ?? ''));
        $name = trim((string) ($args['name'] ?? ''));

        if ($email === '' && $phone === '') {
            return ['created' => false, 'reason' => 'missing_contact'];
        }

        $contact = Contact::query()
            ->when($email, fn ($q) => $q->orWhere('email', $email))
            ->when($phone, fn ($q) => $q->orWhere('phone', $phone))
            ->first();

        if (! $contact) {
            $contact = Contact::create([
                'name' => $name ?: 'Visitante',
                'email' => $email ?: null,
                'phone' => $phone ?: null,
                'last_activity_at' => now(),
            ]);
        }

        $lead = Lead::create([
            'contact_id' => $contact->id,
            'source_channel' => $context->conversation?->channel ?? 'web',
            'intent' => $args['intent'] ?? 'interes',
            'lead_score' => (int) ($args['score'] ?? 10),
            'status' => 'new',
        ]);

        AnalyticsEvent::create([
            'kind' => 'lead_generated',
            'context' => ['contact_id' => $contact->id, 'tool' => 'create_lead', 'lead_id' => $lead->id],
        ]);

        return ['created' => true, 'contact_id' => $contact->id, 'lead_id' => $lead->id];
    }
}
