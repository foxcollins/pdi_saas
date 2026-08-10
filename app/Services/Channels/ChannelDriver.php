<?php

namespace App\Services\Channels;

use Illuminate\Http\Request;

interface ChannelDriver
{
    public function name(): string;

    /**
     * Verifica la firma del webhook entrante (HMAC o challenge específico del canal).
     */
    public function verify(Request $request): bool;

    /**
     * Convierte el payload entrante en un mensaje normalizado.
     *
     * @return array{message: string, visitor: array, external_id: string, tenant_hint: ?string}
     */
    public function parseInbound(Request $request): array;

    /**
     * Envía un mensaje de respuesta por el canal.
     */
    public function send(string $externalId, string $text, array $options = []): array;
}
