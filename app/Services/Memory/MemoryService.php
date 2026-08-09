<?php

namespace App\Services\Memory;

use App\Models\Contact;
use App\Models\Conversation;
use App\Models\CustomerMemory;
use Illuminate\Support\Str;

class MemoryService
{
    private const RETENTION_DAYS = 365;

    public function remember(Conversation $conversation, ?callable $aiExtract = null): void
    {
        if ($conversation->contact_id === null || $conversation->contact_id === '') {
            return;
        }

        $messages = $conversation->messages()
            ->orderBy('created_at')
            ->get()
            ->map(fn ($m) => ['role' => $m->direction === 'in' ? 'user' : 'assistant', 'content' => $m->content])
            ->all();

        if (count($messages) < 2) {
            return;
        }

        $facts = $this->extractFacts($messages);

        if (collect($facts['preferences'])->isEmpty() && collect($facts['interests'])->isEmpty()) {
            return;
        }

        CustomerMemory::create([
            'contact_id' => $conversation->contact_id,
            'kind' => 'summary',
            'content' => [
                'summary' => $facts['summary'],
                'preferences' => $facts['preferences'],
                'interests' => $facts['interests'],
            ],
            'window_start' => $conversation->started_at ?: $conversation->created_at,
            'window_end' => $conversation->ended_at ?: now(),
            'policy' => 'retain_'.$this->retentionDays(),
        ]);
    }

    public function consolidate(Contact $contact): void
    {
        $memories = CustomerMemory::query()
            ->where('contact_id', $contact->id)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        if ($memories->isEmpty()) {
            return;
        }

        $preferences = collect();
        $interests = collect();

        foreach ($memories as $memory) {
            $preferences = $preferences->merge($memory->content['preferences'] ?? []);
            $interests = $interests->merge($memory->content['interests'] ?? []);
        }

        $contact->update([
            'memory_summary' => [
                'summary' => $memories->first()['content']['summary'] ?? null,
                'preferences' => $preferences->unique()->values()->take(30),
                'interests' => $interests->unique()->values()->take(30),
            ],
            'lifecycle' => 'lead',
        ]);
    }

    public function setConsent(Contact $contact, string $status): void
    {
        $contact->update(['consent_status' => $status]);
    }

    public function forget(Contact $contact, bool $anonymize = true): void
    {
        CustomerMemory::query()->where('contact_id', $contact->id)->delete();

        $contact->conversations()->each(function (Conversation $conversation) {
            $conversation->messages()->delete();
            $conversation->delete();
        });

        $contact->leads()->delete();

        if ($anonymize) {
            $contact->update([
                'name' => 'Persona anónima',
                'email' => null,
                'phone' => null,
                'whatsapp_id' => null,
                'instagram_username' => null,
                'tags' => [],
                'memory_summary' => [],
                'consent_status' => 'revoked',
                'anonymized_at' => now(),
            ]);
        } else {
            $contact->delete();
        }
    }

    public function pruneExpired(?int $days = null): int
    {
        $days = $days ?: $this->retentionDays();

        $expired = CustomerMemory::query()
            ->where(function ($q) use ($days) {
                $q->where('window_end', '<', now()->subDays($days))
                    ->orWhereNull('window_end')
                    ->orWhere('created_at', '<', now()->subDays($days));
            });

        $count = $expired->count();
        $expired->delete();

        return $count;
    }

    private function extractFacts(array $messages): array
    {
        $userText = collect($messages)
            ->filter(fn ($m) => $m['role'] === 'user')
            ->pluck('content')
            ->implode(' ');

        $preferences = [];
        $interests = [];

        foreach ($this->patterns() as $kind => $regexes) {
            foreach ($regexes as $regex) {
                if (preg_match_all($regex, $userText, $matches)) {
                    foreach ($matches[1] as $value) {
                        $value = trim($value);
                        if ($value !== '') {
                            if ($kind === 'preferences') {
                                $preferences[] = $value;
                            } else {
                                $interests[] = $value;
                            }
                        }
                    }
                }
            }
        }

        return [
            'preferences' => array_values(array_unique($preferences)),
            'interests' => array_values(array_unique($interests)),
            'summary' => Str::limit($this->buildSummary($userText), 500),
        ];
    }

    private function patterns(): array
    {
        return [
            'interests' => [
                '/(?:interesado|interesada|busco|necesito|quiero|consulta sobre|presupuesto para)\s+(?:en|por|sobre)?\s*([^.,;?!]{3,60})/iu',
                '/(?:mi\s+)?(?:producto|servicio|tema|sector)\s*(?:favorito|de interes)\s*(?::|es)\s*([^.,;?!]{3,60})/iu',
            ],
            'preferences' => [
                '/(?:prefiero|preferimos|me gusta|nos gusta)\s+(?:el|la|los|las|ser|recibir|que|por|en)?\s*([^.,;?!]{3,60})/iu',
                '/(?:horario|hora)\s*(?:de)?\s*(?:preferido|preferida)?\s*(?:es|de|entre)?\s*([^.,;?!]{3,60})/iu',
            ],
        ];
    }

    private function buildSummary(string $userText): string
    {
        return trim(preg_replace('/\s+/u', ' ', $userText));
    }

    private function retentionDays(): int
    {
        return (int) (config('memory.retention_days') ?: self::RETENTION_DAYS);
    }
}
