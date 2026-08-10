<?php

namespace App\Services\Channels;

use RuntimeException;

class ChannelException extends RuntimeException
{
    public static function invalidSignature(): self
    {
        return new self('Firma de webhook inválida.');
    }

    public static function channelDisabled(string $channel): self
    {
        return new self("El canal {$channel} no está configurado.");
    }

    public static function sendFailed(string $channel, string $reason): self
    {
        return new self("No se pudo enviar por {$channel}: {$reason}");
    }
}
