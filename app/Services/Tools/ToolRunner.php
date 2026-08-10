<?php

namespace App\Services\Tools;

use App\Models\ToolRun;
use Illuminate\Support\Facades\Validator;

class ToolRunner
{
    /** @var array<string, class-string<Tool>> */
    protected array $registry = [];

    public function register(string $name, string $class): void
    {
        $this->registry[$name] = $class;
    }

    public function resolve(string $name): Tool
    {
        $class = $this->registry[$name] ?? null;

        if (! $class) {
            throw ToolException::notFound($name);
        }

        return new $class;
    }

    public function available(): array
    {
        return array_keys($this->registry);
    }

    public function run(string $name, array $args, ToolContext $context): array
    {
        $tool = $this->resolve($name);

        if (! $this->isEnabled($name, $context)) {
            throw ToolException::missingPermission($name);
        }

        $this->validate($tool, $args);

        $start = hrtime(true);

        try {
            $output = $tool->execute($args, $context);
            $status = 'ok';
            $error = null;
        } catch (\Throwable $e) {
            $output = ['error' => $e->getMessage()];
            $status = 'error';
            $error = $e->getMessage();
        }

        ToolRun::create([
            'tenant_id' => $context->tenant->id,
            'agent_id' => $context->agent?->id,
            'conversation_id' => $context->conversation?->id,
            'tool' => $name,
            'input' => $args,
            'output' => $output,
            'status' => $status,
            'error' => $error,
            'latency_ms' => (int) ((hrtime(true) - $start) / 1e6),
        ]);

        if ($status === 'error') {
            throw new ToolException($error);
        }

        return $output;
    }

    protected function isEnabled(string $name, ToolContext $context): bool
    {
        $enabled = $context->agent?->tools ?? [];

        if (is_string($enabled)) {
            $enabled = json_decode($enabled, true) ?? [];
        }

        $enabled = is_array($enabled) ? $enabled : [];

        return in_array($name, $enabled, true);
    }

    protected function validate(Tool $tool, array $args): void
    {
        $rules = collect($tool->parameters())
            ->mapWithKeys(fn ($param, $key) => [
                $key => $param['required'] ? ['required'] : ['nullable'],
            ])
            ->all();

        $validator = Validator::make($args, $rules);

        if ($validator->fails()) {
            throw new ToolException('Argumentos inválidos para '.$tool->name().': '.implode(', ', $validator->errors()->all()));
        }
    }
}
