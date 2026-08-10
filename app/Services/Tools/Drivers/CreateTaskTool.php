<?php

namespace App\Services\Tools\Drivers;

use App\Models\Contact;
use App\Models\Task;
use App\Services\Tools\BaseTool;
use App\Services\Tools\ToolContext;
use Illuminate\Support\Carbon;

class CreateTaskTool extends BaseTool
{
    public function name(): string
    {
        return 'create_task';
    }

    public function description(): string
    {
        return 'Crea una tarea de seguimiento en el CRM (por ejemplo: llamar al cliente, preparar propuesta).';
    }

    public function permission(): string
    {
        return 'internal';
    }

    public function parameters(): array
    {
        return [
            'title' => ['type' => 'string', 'required' => true, 'description' => 'Título de la tarea.'],
            'description' => ['type' => 'string', 'required' => false, 'description' => 'Detalle de la tarea.'],
            'contact_phone' => ['type' => 'string', 'required' => false, 'description' => 'Teléfono del contacto relacionado.'],
            'due_at' => ['type' => 'string', 'required' => false, 'description' => 'Fecha de vencimiento ISO 8601.'],
        ];
    }

    public function execute(array $args, ToolContext $context): array
    {
        $contactId = null;
        $phone = trim((string) ($args['contact_phone'] ?? ''));

        if ($phone !== '') {
            $contactId = Contact::query()->where('phone', $phone)->value('id');
        }

        $task = Task::create([
            'tenant_id' => $context->tenant->id,
            'contact_id' => $contactId,
            'title' => trim((string) $args['title']),
            'description' => $args['description'] ?? null,
            'status' => 'open',
            'due_at' => isset($args['due_at']) ? Carbon::parse($args['due_at']) : null,
        ]);

        return ['created' => true, 'task_id' => $task->id];
    }
}
