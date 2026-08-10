<?php

namespace App\Services\Channels;

use Illuminate\Http\Request;

class EmailDriver extends BaseHttpDriver
{
    public function verify(Request $request): bool
    {
        $secret = $request->input('secret', '');

        return $secret !== '' && $this->webhookSecret !== null && hash_equals((string) $this->webhookSecret, (string) $secret);
    }

    public function parseInbound(Request $request): array
    {
        $from = (string) $request->input('from', '');
        $subject = (string) $request->input('subject', '');
        $body = (string) $request->input('body', '');

        $text = $body !== '' ? $body : $subject;

        return [
            'message' => $text,
            'visitor' => ['name' => '', 'email' => $from],
            'external_id' => $from,
            'tenant_hint' => null,
        ];
    }

    public function send(string $externalId, string $text, array $options = []): array
    {
        throw ChannelException::sendFailed('email', 'El envío transaccional de email se gestiona vía provider (Resend/Mailgun) en fase posterior.');
    }
}
