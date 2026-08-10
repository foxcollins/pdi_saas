<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Starter',
                'slug' => 'starter',
                'price_monthly' => 19,
                'limits' => [
                    'docs' => 5, 'pages' => 1, 'channels' => 1, 'ai_budget_month' => 100_000,
                    'monthly_messages' => 500, 'daily_messages' => 100, 'per_minute' => 5, 'max_tokens' => 600,
                ],
            ],
            [
                'name' => 'Business',
                'slug' => 'business',
                'price_monthly' => 49,
                'limits' => [
                    'docs' => 30, 'pages' => 5, 'channels' => 3, 'ai_budget_month' => 500_000,
                    'monthly_messages' => 2_000, 'daily_messages' => 300, 'per_minute' => 10, 'max_tokens' => 800,
                ],
            ],
            [
                'name' => 'Pro',
                'slug' => 'pro',
                'price_monthly' => 99,
                'limits' => [
                    'docs' => 100, 'pages' => 20, 'channels' => 5, 'ai_budget_month' => 2_000_000,
                    'monthly_messages' => 10_000, 'daily_messages' => 1_000, 'per_minute' => 20, 'max_tokens' => 1200,
                ],
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'price_monthly' => 299,
                'limits' => [
                    'docs' => 1000, 'pages' => 100, 'channels' => 10, 'ai_budget_month' => 10_000_000,
                    'monthly_messages' => 50_000, 'daily_messages' => 5_000, 'per_minute' => 60, 'max_tokens' => 2000,
                ],
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(['slug' => $plan['slug']], $plan);
        }
    }
}
