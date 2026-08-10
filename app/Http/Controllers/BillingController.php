<?php

namespace App\Http\Controllers;

use App\Services\Billing\PlanService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BillingController extends Controller
{
    public function __construct(private PlanService $plans) {}

    public function show()
    {
        $tenant = tenant();
        $limits = $this->plans->limits($tenant);

        return inertia('Billing', [
            'plan' => $tenant->plan ? $this->plans->present($tenant->plan) : null,
            'plans' => $this->plans->catalog(),
            'limits' => [
                'ai' => $limits,
                'docs' => $limits['docs'] ?? null,
                'pages' => $limits['pages'] ?? null,
                'channels' => $limits['channels'] ?? null,
            ],
            'usage' => $this->plans->usage($tenant),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'plan' => ['required', 'string', 'max:50', Rule::exists('plans', 'slug')],
        ]);

        $plan = $this->plans->setPlan(tenant(), $data['plan']);

        return back()->with('success', "Plan actualizado a {$plan->name}.");
    }
}
