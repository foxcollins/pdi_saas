<?php

namespace App\Services\Channels;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class TelegramDriver extends BaseHttpDriver
{
    public function verify(Request $request): bool
    {
        return true;
    }

    public function parseInbound(Request $request): array
    {
        $update = $request->input('update_id', null);
        $message = $request->input('message', []);

        if ($update === null && $request->has('message')) {
            $message = $request->input('message');
        }

        $chatId = (string) data_get($message, 'chat.id', '');
        $firstName = (string) data_get($message, 'chat.first_name', '');
        $username = (string) data_get($message, 'from.username', '');
        $text = (string) data_get($message, 'text', '');

        if ($text === '') {
            $text = (string) data_get($message, 'caption', '');
        }

        return [
            'message' => $text,
            'visitor' => ['name' => $firstName ?: $username],
            'external_id' => $chatId,
            'tenant_hint' => null,
        ];
    }

    public function send(string $externalId, string $text, array $options = []): array
    {
        $base = rtrim($this->config('base_url', 'https://api.telegram.org/bot'), '/');
        $token = $this->config('bot_token');

        $response = Http::post("{$base}/{$token}/sendMessage", [
            'chat_id' => $externalId,
            'text' => $text,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true,
        ]);

        if ($response->failed()) {
            throw ChannelException::sendFailed('telegram', $response->body());
        }

        return ['ok' => true, 'message_id' => $response->json('result.message_id')];
    }
}
