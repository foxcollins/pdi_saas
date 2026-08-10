<?php

namespace App\Http\Controllers;

use App\Models\Integration;
use App\Services\Billing\PlanService;
use App\Services\Channels\ChannelManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class IntegrationsController extends Controller
{
    public function __construct(
        private PlanService $plans,
        private ChannelManager $manager,
    ) {}

    public function index()
    {
        $tenant = tenant();

        $integrations = Integration::query()
            ->get()
            ->keyBy('channel');

        $channels = collect(config('channels.channels'))
            ->map(function (array $def, string $channel) use ($integrations, $tenant) {
                $integration = $integrations->get($channel);

                return [
                    'key' => $channel,
                    'label' => $def['label'],
                    'icon' => $def['icon'],
                    'fields' => $def['fields'],
                    'webhook_url' => $this->manager->webhookUrl($channel, $tenant->slug),
                    'connected' => (bool) $integration,
                    'status' => $integration?->status ?? 'disabled',
                    'last_sync_at' => $integration?->last_sync_at,
                ];
            })
            ->values();

        return inertia('Integrations', [
            'channels' => $channels,
            'limits' => $this->plans->limits($tenant),
            'usage' => $this->plans->usage($tenant),
        ]);
    }

    public function store(Request $request, string $channel)
    {
        $def = config("channels.channels.{$channel}");

        abort_unless($def, 404, 'Canal no soportado.');

        $tenant = tenant();

        $rules = collect($def['fields'])->mapWithKeys(function ($field, $key) {
            $rule = $field['required'] ? ['required', 'string', 'max:2048'] : ['nullable', 'string', 'max:2048'];

            return [$key => $rule];
        })->all();

        $data = $request->validate($rules);

        $current = Integration::query()->where('channel', $channel)->first();

        $config = $current?->config_encrypted
            ? json_decode(Crypt::decryptString($current->config_encrypted), true) ?? []
            : [];

        foreach ($data as $key => $value) {
            if (! empty($value)) {
                $config[$key] = $value;
            }
        }

        Integration::updateOrCreate(
            ['tenant_id' => $tenant->id, 'channel' => $channel],
            [
                'provider' => $def['provider'],
                'config_encrypted' => Crypt::encryptString(json_encode($config)),
                'status' => 'active',
                'webhook_secret' => $current?->webhook_secret ?? Str::random(48),
                'last_sync_at' => now(),
            ]
        );

        return back()->with('success', "Canal {$def['label']} configurado.");
    }

    public function disable(string $channel)
    {
        $integration = Integration::query()->where('channel', $channel)->first();

        if ($integration) {
            $integration->update(['status' => 'disabled']);
        }

        return back()->with('success', 'Canal desactivado.');
    }
}
