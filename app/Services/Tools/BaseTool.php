<?php

namespace App\Services\Tools;

abstract class BaseTool implements Tool
{
    abstract public function name(): string;

    abstract public function description(): string;

    abstract public function parameters(): array;

    public function permission(): string
    {
        return 'internal';
    }

    public function definition(): array
    {
        return [
            'name' => $this->name(),
            'description' => $this->description(),
            'permission' => $this->permission(),
            'parameters' => $this->parameters(),
        ];
    }
}
