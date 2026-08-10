<?php

namespace Tests\Feature;

use App\Models\AiRun;
use App\Services\Ai\AiUsageLimitException;
use App\Services\Ai\AiUsageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AiUsageTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_tenant_puede_configurar_su_limite_mensual(): void
    {
        $tenant = $this->makeTenant('Uso IA', 'uso-ia');
        $tenant->update(['settings' => ['ai' => ['monthly_messages' => 1]]]);
        $this->switchTenant($tenant);
        RateLimiter::clear("ai-chat:{$tenant->id}");

        AiRun::create([
            'trigger' => 'chat',
            'model_profile_id' => 'test',
        ]);

        $this->expectException(AiUsageLimitException::class);
        app(AiUsageService::class)->assertCanChat($tenant);
    }

    public function test_el_rate_limit_rechaza_consultas_excesivas(): void
    {
        $tenant = $this->makeTenant('Rate IA', 'rate-ia');
        $tenant->update(['settings' => ['ai' => ['per_minute' => 1]]]);
        $this->switchTenant($tenant);
        RateLimiter::clear("ai-chat:{$tenant->id}");

        $service = app(AiUsageService::class);
        $service->assertCanChat($tenant);

        $this->expectException(AiUsageLimitException::class);
        $service->assertCanChat($tenant);
    }
}
