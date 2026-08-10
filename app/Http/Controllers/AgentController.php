<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use Illuminate\Http\Request;

class AgentController extends Controller
{
    public function show()
    {
        $agent = $this->agent();

        return inertia('Agent', [
            'agent' => [
                'id' => $agent->id,
                'name' => $agent->name,
                'instructions' => $agent->instructions,
                'is_active' => $agent->is_active,
                'tone' => $agent->guardrails['tone'] ?? 'profesional y cercano',
                'language' => $agent->guardrails['language'] ?? 'español',
                'welcome' => $agent->guardrails['welcome'] ?? 'Hola, ¿en qué puedo ayudarte?',
                'escalation' => $agent->guardrails['escalation'] ?? 'Cuando no tengas información suficiente, deriva a un asesor humano.',
            ],
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'instructions' => ['nullable', 'string', 'max:5000'],
            'tone' => ['required', 'string', 'max:100'],
            'language' => ['required', 'string', 'in:español'],
            'welcome' => ['required', 'string', 'max:255'],
            'escalation' => ['required', 'string', 'max:1000'],
            'is_active' => ['required', 'boolean'],
        ]);

        $agent = $this->agent();
        $agent->update([
            'name' => $data['name'],
            'instructions' => $data['instructions'] ?? null,
            'is_active' => $data['is_active'],
            'guardrails' => [
                'tone' => $data['tone'],
                'language' => $data['language'],
                'welcome' => $data['welcome'],
                'escalation' => $data['escalation'],
            ],
        ]);

        return back()->with('success', 'Asistente actualizado.');
    }

    private function agent(): Agent
    {
        return Agent::query()->firstOrCreate(
            ['tenant_id' => tenant()->id, 'slug' => 'assistant'],
            [
                'name' => 'Asistente de '.tenant()->name,
                'instructions' => 'Responde con la información autorizada del negocio.',
                'tools' => [],
                'guardrails' => [
                    'tone' => 'profesional y cercano',
                    'language' => 'español',
                    'welcome' => 'Hola, ¿en qué puedo ayudarte?',
                    'escalation' => 'Cuando no tengas información suficiente, deriva a un asesor humano.',
                ],
                'is_active' => true,
            ]
        );
    }
}
