<?php

namespace App\Services\Channels;

use App\Models\Integration;
use Illuminate\Support\Facades\Crypt;

class ChannelManager
{
    public function driver(Integration $integration): ChannelDriver
    {
        $config = config("channels.channels.{$integration->channel}", []);

        if (config('channels.testing')) {
            return new FakeChannelDriver;
        }

        $decoded = $integration->config_encrypted
            ? json_decode(Crypt::decryptString($integration->config_encrypted), true)
            : [];

        $merged = array_merge($config['meta'] ?? [], $decoded);

        return match ($integration->channel) {
            'whatsapp' => new WhatsAppDriver($merged, $integration->webhook_secret),
            'messenger' => new MessengerDriver($merged, $integration->webhook_secret),
            'instagram' => new MessengerDriver($merged, $integration->webhook_secret, 'instagram'),
            'telegram' => new TelegramDriver($merged, $integration->webhook_secret),
            'email' => new EmailDriver($merged, $integration->webhook_secret),
            default => throw ChannelException::channelDisabled($integration->channel),
        };
    }

    public function webhookUrl(string $channel, ?string $tenantSlug = null): string
    {
        $base = config('channels.webhook_path', 'webhooks');

        return $tenantSlug
            ? url("/{$base}/{$channel}/{$tenantSlug}")
            : url("/{$base}/{$channel}");
    }
}
