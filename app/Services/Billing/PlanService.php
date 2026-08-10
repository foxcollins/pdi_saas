<?php

namespace App\Services\Billing;

use App\Models\AiRun;
use App\Models\Conversation;
use App\Models\KnowledgeDocument;
use App\Models\Plan;
use App\Models\Tenant;

class PlanService
{
    public function limits(Tenant $tenant): array
    {
        $defaults = config('ai.usage');
        $plan = $tenant->plan;

        $planLimits = $plan ? array_merge($defaults, $plan->limits ?? []) : $defaults;

        return array_merge($planLimits, $tenant->settings['ai'] ?? []);
    }

    public function usage(Tenant $tenant): array
    {
        $monthStart = now()->startOfMonth();

        $documents = KnowledgeDocument::count();
        $pages = collect($tenant->website?->pages ?? [])->count();
        $channels = Conversation::query()->select('channel')->distinct()->count();

        $aiRuns = AiRun::query()
            ->where('trigger', 'chat')
            ->where('created_at', '>=', $monthStart);

        return [
            'documents' => $documents,
            'pages' => $pages,
            'channels' => $channels,
            'monthly_messages' => (clone $aiRuns)->count(),
            'monthly_cost' => (float) (clone $aiRuns)->sum('cost_usd'),
        ];
    }

    public function catalog(): array
    {
        return Plan::query()
            ->orderBy('price_monthly')
            ->get()
            ->map(fn (Plan $plan) => $this->present($plan))
            ->values()
            ->all();
    }

    public function present(Plan $plan): array
    {
        return [
            'id' => $plan->id,
            'name' => $plan->name,
            'slug' => $plan->slug,
            'price_monthly' => (float) $plan->price_monthly,
            'limits' => $plan->limits ?? [],
        ];
    }

    public function setPlan(Tenant $tenant, string $planSlug): Plan
    {
        $plan = Plan::query()->where('slug', $planSlug)->firstOrFail();

        $tenant->update(['plan_id' => $plan->id]);

        $tenant->subscriptions()->updateOrCreate(
            ['status' => 'active'],
            [
                'plan_id' => $plan->id,
                'current_period_start' => now()->startOfMonth(),
                'current_period_end' => now()->endOfMonth(),
            ]
        );

        return $plan;
    }
}
