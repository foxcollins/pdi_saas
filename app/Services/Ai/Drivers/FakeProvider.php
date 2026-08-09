<?php

namespace App\Services\Ai\Drivers;

use App\Services\Ai\AiProvider;

class FakeProvider implements AiProvider
{
    public function chat(array $messages, array $options = []): string
    {
        $lastUser = collect($messages)->filter(fn ($m) => $m['role'] === 'user')->last()['content'] ?? '';

        return $this->replyFromContext($messages, $lastUser);
    }

    public function chatStream(array $messages, callable $onChunk, array $options = []): void
    {
        $lastUser = collect($messages)->filter(fn ($m) => $m['role'] === 'user')->last()['content'] ?? '';
        $reply = $this->replyFromContext($messages, $lastUser);

        foreach (str_split($reply, 12) as $chunk) {
            $onChunk($chunk);
            usleep(40000);
        }
    }

    public function embed(array $texts): array
    {
        return array_map(fn ($text) => self::hashVector($text), array_values($texts));
    }

    public function name(): string
    {
        return 'fake';
    }

    public static function isFake(AiProvider $provider): bool
    {
        return $provider->name() === 'fake';
    }

    public static function hashVector(string $text): array
    {
        $dim = (int) (config('ai.providers.fake.embedding_dimensions') ?: 1536);
        $v = array_fill(0, $dim, 0.0);

        foreach (str_word_count(mb_strtolower($text), 1) as $word) {
            $index = crc32($word) % $dim;
            $v[$index] += 1.0;
        }

        $norm = sqrt(array_sum(array_map(fn ($x) => $x * $x, $v)));

        if ($norm > 0) {
            foreach ($v as $i => $x) {
                $v[$i] = $x / $norm;
            }
        }

        return $v;
    }

    private function replyFromContext(array $messages, string $lastUser): string
    {
        $system = collect($messages)->filter(fn ($m) => $m['role'] === 'system')->first()['content'] ?? '';
        $knowledge = '';

        if (preg_match('/CONOCIMIENTO:\s*(.*?)\s*REGLAS:/su', $system, $m)) {
            $knowledge = trim($m[1]);
        }

        if ($knowledge !== '') {
            $snippet = str_starts_with($knowledge, '[') ? 'De la información disponible...' : mb_substr($knowledge, 0, 320);

            return "Según la información de {$snippet}... ".mb_substr($knowledge, 0, 380);
        }

        return 'Gracias por tu mensaje. Aún no tengo información suficiente sobre eso en la base de conocimiento, pero un asesor puede ayudarte. Deja tu contacto en el formulario y te responderemos pronto.';
    }
}
