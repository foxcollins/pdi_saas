<?php

namespace App\Services\Ai;

use App\Models\AiRun;
use App\Services\Ai\Drivers\FakeProvider;
use App\Services\Ai\Drivers\GroqProvider;
use App\Services\Ai\Drivers\OpenAiProvider;
use App\Services\Ai\Drivers\OpenRouterProvider;
use Illuminate\Support\Manager;

class AiManager extends Manager
{
    public function getDefaultDriver(): string
    {
        return config('ai.default_provider', 'fake');
    }

    public function createFakeDriver(): FakeProvider
    {
        return new FakeProvider;
    }

    public function createGroqDriver(): GroqProvider
    {
        return new GroqProvider(config('ai.providers.groq'));
    }

    public function createOpenrouterDriver(): OpenRouterProvider
    {
        return new OpenRouterProvider(config('ai.providers.openrouter'));
    }

    public function createOpenaiDriver(): OpenAiProvider
    {
        return new OpenAiProvider(config('ai.providers.openai'));
    }

    public function chat(array $messages, array $options = []): string
    {
        $driver = $this->driver();
        $model = $options['model'] ?? null;
        $start = hrtime(true);

        $text = $driver->chat($messages, $options);

        $this->record([
            'trigger' => $options['trigger'] ?? 'chat',
            'model_profile_id' => $model ?? get_class($driver),
            'tokens_in' => $this->estimateTokens(implode('', array_column($messages, 'content'))),
            'tokens_out' => $this->estimateTokens($text),
            'latency_ms' => (int) ((hrtime(true) - $start) / 1e6),
        ]);

        return $text;
    }

    public function chatStream(array $messages, callable $onChunk, array $options = []): void
    {
        $driver = $this->driver();
        $model = $options['model'] ?? null;
        $start = hrtime(true);
        $full = '';

        $driver->chatStream($messages, function ($chunk) use (&$full, $onChunk) {
            $full .= $chunk;
            $onChunk($chunk);
        }, $options);

        $this->record([
            'trigger' => $options['trigger'] ?? 'chat',
            'model_profile_id' => $model ?? get_class($driver),
            'tokens_in' => $this->estimateTokens(implode('', array_column($messages, 'content'))),
            'tokens_out' => $this->estimateTokens($full),
            'latency_ms' => (int) ((hrtime(true) - $start) / 1e6),
        ]);
    }

    public function embed(array $texts): array
    {
        return $this->driver(config('ai.embedding_provider', 'fake'))->embed($texts);
    }

    public function queryEmbedding(string $text): array
    {
        return $this->embed([$text])[0];
    }

    public function isFake(): bool
    {
        return FakeProvider::isFake($this->driver());
    }

    protected function record(array $data): void
    {
        $model = $data['model_profile_id'] ?? '';
        $prices = config('ai.prices_per_1m.'.$model, config('ai.default_prices'));

        $tokensIn = $data['tokens_in'] ?? 0;
        $tokensOut = $data['tokens_out'] ?? 0;
        $cost = ($tokensIn / 1_000_000 * ($prices['in'] ?? 0)) + ($tokensOut / 1_000_000 * ($prices['out'] ?? 0));

        try {
            AiRun::create([
                'trigger' => $data['trigger'] ?? 'chat',
                'model_profile_id' => $model,
                'tokens_in' => $tokensIn,
                'tokens_out' => $tokensOut,
                'cost_usd' => round($cost, 8),
                'latency_ms' => $data['latency_ms'] ?? 0,
                'cached' => $data['cached'] ?? false,
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    protected function estimateTokens(string $text): int
    {
        return (int) ceil(mb_strlen($text) / 4);
    }
}
