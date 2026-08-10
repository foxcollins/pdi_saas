<?php

namespace App\Services\Channels;

use App\Models\Integration;
use App\Models\Tenant;
use App\Services\Chat\ChatService;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class InboundWebhookService
{
    public function __construct(
        private ChannelManager $manager,
        private ChatService $chat,
    ) {}

    public function handle(string $channel, Request $request, ?string $tenantSlug = null): array
    {
        if (config('channels.testing')) {
            return $this->handleFake($channel, $request, $tenantSlug);
        }

        $tenant = $tenantSlug
            ? Tenant::query()->where('slug', $tenantSlug)->first()
            : null;

        $integration = $this->resolveIntegration($channel, $tenant, $request);

        $driver = $this->manager->driver($integration);

        if (! $driver->verify($request)) {
            throw ChannelException::invalidSignature();
        }

        $parsed = $driver->parseInbound($request);

        if (($parsed['message'] ?? '') === '' && ($parsed['external_id'] ?? '') === '') {
            return ['handled' => false, 'reason' => 'ignored'];
        }

        $integration->update(['last_sync_at' => now()]);

        $this->process($channel, $integration->tenant, $parsed, $driver);

        return ['handled' => true];
    }

    public function handleFake(string $channel, Request $request, ?string $tenantSlug = null): array
    {
        $tenant = $tenantSlug
            ? Tenant::query()->where('slug', $tenantSlug)->first()
            : Tenant::query()->first();

        $driver = new FakeChannelDriver;
        $parsed = $driver->parseInbound($request);

        $reply = $this->process($channel, $tenant, $parsed, $driver);

        return ['handled' => true, 'reply' => $reply];
    }

    private function resolveIntegration(string $channel, ?Tenant $tenant, Request $request): Integration
    {
        if ($tenant) {
            TenantContext::set($tenant->id);

            $integration = Integration::query()
                ->where('channel', $channel)
                ->where('status', 'active')
                ->first();

            if (! $integration) {
                throw ChannelException::channelDisabled($channel);
            }

            return $integration;
        }

        $hint = $this->hintFromRequest($channel, $request);

        if ($hint) {
            $integration = Integration::query()
                ->withoutGlobalScopes()
                ->where('channel', $channel)
                ->where('status', 'active')
                ->get()
                ->first(fn ($i) => $this->configMatches($i, $hint));

            if ($integration) {
                return $integration;
            }
        }

        throw ChannelException::channelDisabled($channel);
    }

    private function hintFromRequest(string $channel, Request $request): ?string
    {
        return match ($channel) {
            'whatsapp' => (string) data_get($request->input('entry.0.changes.0.value.metadata.phone_number_id'), ''),
            'messenger', 'instagram' => (string) data_get($request->input('entry.0.id'), ''),
            'email' => (string) $request->input('from', ''),
            default => null,
        };
    }

    private function configMatches(Integration $integration, string $hint): bool
    {
        if (! $integration->config_encrypted) {
            return false;
        }

        $config = json_decode(Crypt::decryptString($integration->config_encrypted), true) ?? [];

        return in_array($hint, [
            $config['phone_number_id'] ?? null,
            $config['page_id'] ?? null,
            $config['instagram_user_id'] ?? null,
            $config['from_address'] ?? null,
        ], true);
    }

    private function process(string $channel, Tenant $tenant, array $parsed, ChannelDriver $driver): ?string
    {
        $message = (string) ($parsed['message'] ?? '');
        $externalId = (string) ($parsed['external_id'] ?? '');

        if ($message === '' || $externalId === '') {
            return null;
        }

        $cacheKey = 'inbound:'.md5($channel.':'.$externalId.':'.$message.':'.$tenant->id);

        if (Cache::has($cacheKey)) {
            return null;
        }

        Cache::put($cacheKey, true, now()->addMinutes(5));

        TenantContext::set($tenant->id);

        $result = $this->chat->respond(
            $tenant->slug,
            $message,
            $parsed['visitor'] ?? [],
            null,
            $channel,
            $externalId,
        );

        if (! empty($result['reply'])) {
            $driver->send($externalId, $result['reply']);
        }

        return $result['reply'] ?? null;
    }
}
