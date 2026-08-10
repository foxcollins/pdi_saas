<?php

namespace App\Services\Tools;

interface Tool
{
    public function name(): string;

    public function description(): string;

    /**
     * Nivel de permiso: read | internal | external | destructive.
     */
    public function permission(): string;

    /**
     * Parámetros de entrada (convención: lista de claves con tipo y descripción).
     */
    public function parameters(): array;

    /**
     * Ejecuta la tool dentro del contexto del tenant y devuelve un array serializable.
     */
    public function execute(array $args, ToolContext $context): array;

    /**
     * Metadatos completos para el panel y el LLM.
     */
    public function definition(): array;
}
