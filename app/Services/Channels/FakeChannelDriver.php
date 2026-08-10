<?php

namespace App\Services\Channels;

use Illuminate\Http\Request;

class FakeChannelDriver implements ChannelDriver
{
    public function name(): string
    {
        return 'fake';
    }

    public function verify(Request $request): bool
    {
        return true;
    }

    public function parseInbound(Request $request): array
    {
        return [
            'message' => (string) ($request->input('message') ?? $request->input('text', '')),
            'visitor' => [
                'name' => $request->input('name', ''),
                'phone' => $request->input('phone', ''),
                'email' => $request->input('email', ''),
            ],
            'external_id' => (string) ($request->input('external_id') ?? $request->input('chat_id', '')),
            'tenant_hint' => $request->input('tenant'),
        ];
    }

    public function send(string $externalId, string $text, array $options = []): array
    {
        return ['ok' => true, 'message_id' => 'fake-'.md5($externalId.$text)];
    }
}
