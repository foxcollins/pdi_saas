<?php

namespace App\Http\Controllers;

use App\Models\Integration;
use App\Models\Tenant;
use App\Services\Channels\ChannelException;
use App\Services\Channels\InboundWebhookService;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function __construct(private InboundWebhookService $inbound) {}

    public function verify(Request $request, string $channel, ?string $tenantSlug = null)
    {
        $verifyToken = (string) $request->input('hub_verify_token');
        $challenge = (string) $request->input('hub_challenge');

        if ($verifyToken === '' || $challenge === '') {
            return response()->json(['error' => 'missing params'], 400);
        }

        $query = Integration::query()
            ->where('channel', $channel)
            ->where('status', 'active');

        if ($tenantSlug) {
            $tenant = Tenant::query()->where('slug', $tenantSlug)->first();

            if (! $tenant) {
                return response()->json(['error' => 'tenant not found'], 404);
            }

            $query->where('tenant_id', $tenant->id);
        }

        $integration = $query->first();

        if (! $integration || ! $integration->webhook_secret || ! hash_equals($integration->webhook_secret, $verifyToken)) {
            return response()->json(['error' => 'invalid token'], 403);
        }

        $integration->update(['last_sync_at' => now()]);

        return response($challenge, 200)->header('Content-Type', 'text/plain');
    }

    public function handle(Request $request, string $channel, ?string $tenantSlug = null)
    {
        try {
            $result = $this->inbound->handle($channel, $request, $tenantSlug);

            if ($result['handled'] === false) {
                return response()->json(['status' => 'ignored'], 200);
            }

            return response()->json(['status' => 'ok']);
        } catch (ChannelException $e) {
            return response()->json(['error' => $e->getMessage()], 403);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['error' => 'Internal error'], 500);
        }
    }

    public function fake(Request $request, string $channel, ?string $tenantSlug = null)
    {
        try {
            $result = $this->inbound->handleFake($channel, $request, $tenantSlug);

            return response()->json($result);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['error' => 'Internal error'], 500);
        }
    }
}
