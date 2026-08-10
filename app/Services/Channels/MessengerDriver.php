<?php

namespace App\Services\Channels;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class MessengerDriver extends BaseHttpDriver
{
    public function __construct(array $config = [], ?string $webhookSecret = null, protected string $channel = 'messenger')
    {
        parent::__construct($config, $webhookSecret);
        $this->channel = $channel;
    }

    public function verify(Request $request): bool
    {
        if ($request->filled('hub_challenge') && $request->filled('hub_verify_token')) {
            return hash_equals((string) $this->webhookSecret, (string) $request->input('hub_verify_token'));
        }

        return $this->hmacValid($request, 'X-Hub-Signature-256');
    }

    public function parseInbound(Request $request): array
    {
        $sender = (string) data_get($request->input('entry.0.messaging.0.sender.id'), '');
        $text = (string) data_get($request->input('entry.0.messaging.0.message.text'), '');

        return [
            'message' => $text,
            'visitor' => ['name' => ''],
            'external_id' => $sender,
            'tenant_hint' => $this->channel === 'instagram'
                ? (string) data_get($request->input('entry.0.id'), '')
                : (string) data_get($request->input('entry.0.id'), ''),
        ];
    }

    public function send(string $externalId, string $text, array $options = []): array
    {
        $base = rtrim($this->config('base_url', 'https://graph.facebook.com/v21.0'), '/');
        $pageId = $this->config('page_id');
        $token = $this->config('page_access_token');

        $endpoint = $this->channel === 'instagram' ? 'me/messages' : "{$pageId}/messages";

        $response = Http::withToken($token)
            ->post("{$base}/{$endpoint}", [
                'recipient' => ['id' => $externalId],
                'message' => ['text' => $text],
            ]);

        if ($response->failed()) {
            throw ChannelException::sendFailed($this->channel, $response->body());
        }

        return ['ok' => true, 'message_id' => $response->json('message_id')];
    }
}
