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
                'limits' => ['docs' => 5, 'pages' => 1, 'channels' => 1, 'ai_budget_month' => 100_000],
            ],
            [
                'name' => 'Business',
                'slug' => 'business',
                'price_monthly' => 49,
                'limits' => ['docs' => 30, 'pages' => 5, 'channels' => 3, 'ai_budget_month' => 500_000],
            ],
            [
                'name' => 'Pro',
                'slug' => 'pro',
                'price_monthly' => 99,
                'limits' => ['docs' => 100, 'pages' => 20, 'channels' => 5, 'ai_budget_month' => 2_000_000],
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'price_monthly' => 299,
                'limits' => ['docs' => 1000, 'pages' => 100, 'channels' => 10, 'ai_budget_month' => 10_000_000],
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(['slug' => $plan['slug']], $plan);
        }
    }
}
