<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Plan;
use App\Models\User;
use App\Services\Ai\AiUsageService;
use App\Services\Billing\PlanService;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingTest extends TestCase
{
    use RefreshDatabase;

    private function makePlans(): void
    {
        $this->seed(PlanSeeder::class);
    }

    private function authTenant(string $slug = 'billing-demo', string $planSlug = 'starter'): array
    {
        $user = User::factory()->create();
        $tenant = $this->makeTenant('Billing Demo', $slug);
        $tenant->users()->attach($user->id, ['role' => 'owner']);
        $tenant->update(['plan_id' => Plan::where('slug', $planSlug)->value('id')]);
        $this->switchTenant($tenant);
        $this->actingAs($user)->withSession(['current_tenant_id' => $tenant->id]);

        return [$user, $tenant];
    }

    public function test_la_pagina_de_plan_entrega_limites_y_consumo(): void
    {
        $this->makePlans();
        [, $tenant] = $this->authTenant('billing-demo', 'starter');

        $contact = Contact::create(['name' => 'Ana']);
        Conversation::create(['contact_id' => $contact->id, 'status' => 'open', 'channel' => 'web']);

        $this->get('/app/billing')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Billing')
                ->where('plan.slug', 'starter')
                ->where('limits.ai.monthly_messages', 500)
                ->where('limits.docs', 5)
                ->where('usage.documents', 0)
                ->where('usage.channels', 1)
                ->has('plans', 4));
    }

    public function test_el_plan_define_los_limites_del_usage_service(): void
    {
        $this->makePlans();
        [, $tenant] = $this->authTenant('billing-business', 'business');

        $limits = app(PlanService::class)->limits($tenant);

        $this->assertSame(2000, $limits['monthly_messages']);
        $this->assertSame(800, $limits['max_tokens']);
        $this->assertSame(800, app(AiUsageService::class)->maxTokens($tenant));
    }

    public function test_el_tenant_puede_cambiar_de_plan(): void
    {
        $this->makePlans();
        [, $tenant] = $this->authTenant('billing-upgrade', 'starter');

        $this->put('/app/billing', ['plan' => 'pro'])
            ->assertRedirect();

        $tenant->refresh();

        $this->assertSame('pro', $tenant->plan?->slug);
        $this->assertSame(10000, app(PlanService::class)->limits($tenant)['monthly_messages']);
        $this->assertSame('pro', $tenant->subscriptions()->first()?->plan->slug);
    }

    public function test_el_cambio_a_un_plan_inexistente_es_validado(): void
    {
        $this->makePlans();
        $this->authTenant('billing-invalid', 'starter');

        $this->put('/app/billing', ['plan' => 'gold'])
            ->assertSessionHasErrors('plan');
    }
}
