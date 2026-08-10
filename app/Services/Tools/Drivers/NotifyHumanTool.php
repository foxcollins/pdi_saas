<?php

namespace App\Services\Tools\Drivers;

use App\Services\Tools\BaseTool;
use App\Services\Tools\ToolContext;

class NotifyHumanTool extends BaseTool
{
    public function name(): string
    {
        return 'notify_human';
    }

    public function description(): string
    {
        return 'Marca la conversación como escalada para que un asesor humano la atienda en la bandeja del CRM.';
    }

    public function permission(): string
    {
        return 'internal';
    }

    public function parameters(): array
    {
        return [
            'reason' => ['type' => 'string', 'required' => false, 'description' => 'Motivo de la escalada.'],
        ];
    }

    public function execute(array $args, ToolContext $context): array
    {
        $conversation = $context->conversation;

        if (! $conversation) {
            return ['escalated' => false, 'reason' => 'no_conversation'];
        }

        $conversation->update([
            'needs_human' => true,
            'escalated_at' => now(),
        ]);

        return [
            'escalated' => true,
            'conversation_id' => $conversation->id,
            'reason' => $args['reason'] ?? null,
        ];
    }
}
