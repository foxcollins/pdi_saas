<?php

namespace App\Services\Channels;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class WhatsAppDriver extends BaseHttpDriver
{
    public function verify(Request $request): bool
    {
        if ($request->filled('hub_challenge') && $request->filled('hub_verify_token')) {
            return hash_equals((string) $this->webhookSecret, (string) $request->input('hub_verify_token'));
        }

        return $this->hmacValid($request, 'X-Hub-Signature-256');
    }

    public function parseInbound(Request $request): array
    {
        $entry = data_get($request->input('entry.0.changes.0.value'), []);

        $from = (string) data_get($entry, 'contacts.0.wa_id', '');
        $name = (string) data_get($entry, 'contacts.0.profile.name', '');
        $text = (string) data_get($entry, 'messages.0.text.body', '');

        if ($text === '') {
            $type = (string) data_get($entry, 'messages.0.type', '');
            $text = $type !== '' ? "[$type]" : '';
        }

        return [
            'message' => $text,
            'visitor' => ['name' => $name, 'phone' => $from],
            'external_id' => $from,
            'tenant_hint' => (string) data_get($entry, 'metadata.phone_number_id', ''),
        ];
    }

    public function send(string $externalId, string $text, array $options = []): array
    {
        $base = rtrim($this->config('base_url', 'https://graph.facebook.com/v21.0'), '/');
        $phoneNumberId = $this->config('phone_number_id');
        $token = $this->config('access_token');

        $response = Http::withToken($token)
            ->post("{$base}/{$phoneNumberId}/messages", [
                'messaging_product' => 'whatsapp',
                'to' => $externalId,
                'type' => 'text',
                'text' => ['body' => $text],
            ]);

        if ($response->failed()) {
            throw ChannelException::sendFailed('whatsapp', $response->body());
        }

        return ['ok' => true, 'message_id' => $response->json('messages.0.id')];
    }
}
