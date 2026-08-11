<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Services\Agents\AgentPresetService;
use App\Services\Billing\PlanService;
use App\Services\Tools\ToolManager;
use Illuminate\Http\Request;

class AgentsController extends Controller
{
    public function index()
    {
        $presets = app(AgentPresetService::class);

        $presets->ensureForTenant(tenant());

        $agents = Agent::query()
            ->orderBy('slug')
            ->get()
            ->map(fn (Agent $agent) => [
                'id' => $agent->id,
                'slug' => $agent->slug,
                'name' => $agent->name,
                'description' => $agent->description,
                'instructions' => $agent->instructions,
                'trigger_keywords' => $agent->trigger_keywords ?? [],
                'tools' => $agent->tools ?? [],
                'is_active' => $agent->is_active,
                'welcome' => $agent->guardrails['welcome'] ?? null,
            ]);

        return inertia('Agents', [
            'agents' => $agents,
            'allowed_tools' => app(PlanService::class)->toolsAllowed(tenant()),
            'tools_catalog' => app(ToolManager::class)->catalog(),
        ]);
    }

    public function update(Request $request, Agent $agent)
    {
        $allowed = app(PlanService::class)->toolsAllowed(tenant());

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'instructions' => ['nullable', 'string', 'max:5000'],
            'trigger_keywords' => ['nullable', 'array'],
            'trigger_keywords.*' => ['string', 'max:60'],
            'tools' => ['nullable', 'array'],
            'tools.*' => ['string', 'in:'.implode(',', $allowed)],
            'is_active' => ['required', 'boolean'],
        ]);

        $agent->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'instructions' => $data['instructions'] ?? null,
            'trigger_keywords' => array_values(array_filter(array_map('trim', $data['trigger_keywords'] ?? []))),
            'tools' => array_values(array_unique($data['tools'] ?? [])),
            'is_active' => $data['is_active'],
        ]);

        return back()->with('success', 'Agente "'.$agent->name.'" actualizado.');
    }
}
