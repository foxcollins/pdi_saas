<?php

namespace App\Services\Tools\Drivers;

use App\Models\WebhookOutbox;
use App\Services\Tools\BaseTool;
use App\Services\Tools\ToolContext;

class N8nWebhookTool extends BaseTool
{
    public function name(): string
    {
        return 'n8n_webhook';
    }

    public function description(): string
    {
        return 'Encola un evento hacia un workflow externo (N8N): lead creado, cotización generada, etc.';
    }

    public function permission(): string
    {
        return 'external';
    }

    public function parameters(): array
    {
        return [
            'event' => ['type' => 'string', 'required' => true, 'description' => 'Nombre del evento (p. ej. lead.created, quote.created).'],
            'payload' => ['type' => 'object', 'required' => false, 'description' => 'Datos a enviar al workflow.'],
        ];
    }

    public function execute(array $args, ToolContext $context): array
    {
        $outbox = WebhookOutbox::create([
            'tenant_id' => $context->tenant->id,
            'event' => trim((string) ($args['event'] ?? '')),
            'payload' => $args['payload'] ?? [],
            'status' => 'pending',
            'attempts' => 0,
        ]);

        return ['queued' => true, 'outbox_id' => $outbox->id, 'event' => $outbox->event];
    }
}
