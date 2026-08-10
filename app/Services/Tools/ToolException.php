<?php

namespace App\Services\Tools;

use RuntimeException;

class ToolException extends RuntimeException
{
    public static function notFound(string $name): self
    {
        return new self("Tool no encontrada: {$name}.");
    }

    public static function missingPermission(string $name): self
    {
        return new self("La tool {$name} no está habilitada para este agente.");
    }
}
