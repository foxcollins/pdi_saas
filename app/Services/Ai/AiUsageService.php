<?php

namespace App\Services\Ai;

use App\Models\AiRun;
use App\Models\Tenant;
use Illuminate\Support\Facades\RateLimiter;

class AiUsageService
{
    public function assertCanChat(Tenant $tenant): void
    {
        $policy = $this->policy($tenant);
        $now = now();

        $monthlyMessages = AiRun::query()
            ->where('trigger', 'chat')
            ->whereBetween('created_at', [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()])
            ->count();

        if ($monthlyMessages >= $policy['monthly_messages']) {
            throw new AiUsageLimitException('Este tenant alcanzó su límite mensual de mensajes IA.');
        }

        $dailyMessages = AiRun::query()
            ->where('trigger', 'chat')
            ->whereBetween('created_at', [$now->copy()->startOfDay(), $now->copy()->endOfDay()])
            ->count();

        if ($dailyMessages >= $policy['daily_messages']) {
            throw new AiUsageLimitException('Este tenant alcanzó su límite diario de mensajes IA.');
        }

        $key = "ai-chat:{$tenant->id}";

        if (RateLimiter::tooManyAttempts($key, $policy['per_minute'])) {
            $seconds = RateLimiter::availableIn($key);

            throw new AiUsageLimitException("Demasiadas consultas. Intenta nuevamente en {$seconds} segundos.");
        }

        RateLimiter::hit($key, 60);
    }

    public function maxTokens(Tenant $tenant): int
    {
        return $this->policy($tenant)['max_tokens'];
    }

    public function summary(Tenant $tenant): array
    {
        $policy = $this->policy($tenant);
        $now = now();
        $monthStart = $now->copy()->startOfMonth();
        $dayStart = $now->copy()->startOfDay();

        $monthRuns = AiRun::query()
            ->where('trigger', 'chat')
            ->where('created_at', '>=', $monthStart);

        return [
            'monthly_messages' => (clone $monthRuns)->count(),
            'monthly_limit' => $policy['monthly_messages'],
            'daily_messages' => (clone $monthRuns)->where('created_at', '>=', $dayStart)->count(),
            'daily_limit' => $policy['daily_messages'],
            'monthly_cost' => (float) (clone $monthRuns)->sum('cost_usd'),
            'max_tokens' => $policy['max_tokens'],
        ];
    }

    private function policy(Tenant $tenant): array
    {
        return array_merge(config('ai.usage'), $tenant->settings['ai'] ?? []);
    }
}
