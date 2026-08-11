<?php

namespace App\Services\Agents;

use App\Models\Agent;
use App\Models\Tenant;
use Illuminate\Support\Str;

class AgentRouter
{
    public function __construct(private AgentPresetService $presets) {}

    /**
     * Resuelve el agente adecuado para un mensaje del visitante.
     * Puntúa los agentes activos por coincidencia de trigger_keywords;
     * si ninguno calza, devuelve el agente general (assistant).
     */
    public function resolve(Tenant $tenant, string $message): Agent
    {
        $this->presets->ensureForTenant($tenant);

        $needle = $this->normalize($message);

        $agents = Agent::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->get();

        $best = null;
        $bestScore = 0;

        foreach ($agents as $agent) {
            $score = $this->score($needle, $agent->trigger_keywords ?? []);

            if ($score > $bestScore) {
                $best = $agent;
                $bestScore = $score;
            }
        }

        if ($best) {
            return $best;
        }

        return Agent::query()
            ->where('tenant_id', $tenant->id)
            ->where('slug', 'assistant')
            ->first()
            ?? Agent::query()->where('tenant_id', $tenant->id)->first()
            ?? new Agent(['name' => 'Asistente virtual', 'tools' => [], 'guardrails' => [], 'trigger_keywords' => []]);
    }

    private function score(string $needle, array $keywords): int
    {
        $score = 0;

        foreach ($keywords as $keyword) {
            $keyword = $this->normalize($keyword);

            if ($keyword !== '' && Str::contains($needle, $keyword)) {
                $score += 1;
            }
        }

        return $score;
    }

    private function normalize(string $value): string
    {
        $value = Str::lower($value);
        $value = str_replace(['á', 'é', 'í', 'ó', 'ú', 'ü', 'ñ'], ['a', 'e', 'i', 'o', 'u', 'u', 'n'], $value);

        return preg_replace('/\s+/u', ' ', $value) ?? $value;
    }
}
