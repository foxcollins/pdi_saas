<?php

namespace App\Services\Ai\Drivers;

use App\Services\Ai\AiProvider;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;

abstract class HttpProvider implements AiProvider
{
    protected Client $client;

    protected string $baseUrl;

    protected string $apiKey;

    protected array $extraHeaders = [];

    protected string $chatModel;

    protected string $fastModel;

    protected string $embeddingModel;

    public function __construct(array $config)
    {
        $this->baseUrl = rtrim($config['base_url'] ?? '', '/');
        $this->apiKey = $config['api_key'] ?? '';
        $this->chatModel = $config['chat_model'] ?? '';
        $this->fastModel = $config['fast_model'] ?? $this->chatModel;
        $this->embeddingModel = $config['embedding_model'] ?? '';
        $this->extraHeaders = $config['http_headers'] ?? [];

        $this->client = new Client(['timeout' => config('ai.timeout', 30)]);
    }

    public function chat(array $messages, array $options = []): string
    {
        $model = $options['model'] ?? $this->chatModel;

        $response = $this->client->post("{$this->baseUrl}/chat/completions", [
            'headers' => $this->headers(),
            'json' => [
                'model' => $model,
                'messages' => $messages,
                'temperature' => $options['temperature'] ?? 0.4,
                'max_tokens' => $options['max_tokens'] ?? 800,
            ],
        ]);

        $data = json_decode($response->getBody()->getContents(), true);

        return $data['choices'][0]['message']['content'] ?? '';
    }

    public function chatStream(array $messages, callable $onChunk, array $options = []): void
    {
        $model = $options['model'] ?? $this->chatModel;

        try {
            $stream = $this->client->request('POST', "{$this->baseUrl}/chat/completions", [
                'headers' => $this->headers(),
                'json' => [
                    'model' => $model,
                    'messages' => $messages,
                    'temperature' => $options['temperature'] ?? 0.4,
                    'max_tokens' => $options['max_tokens'] ?? 800,
                    'stream' => true,
                ],
                'stream' => true,
            ]);
        } catch (ConnectException) {
            $reply = $this->chat($messages, $options);

            foreach (str_split($reply, 80) as $chunk) {
                $onChunk($chunk);
            }

            return;
        }

        foreach ($stream->getBody() as $line) {
            $line = trim((string) $line);

            if ($line === '' || ! str_starts_with($line, 'data:')) {
                continue;
            }

            $payload = json_decode(substr($line, 5), true);

            if (isset($payload['choices'][0]['delta']['content'])) {
                $onChunk($payload['choices'][0]['delta']['content']);
            }
        }
    }

    public function embed(array $texts): array
    {
        $response = $this->client->post("{$this->baseUrl}/embeddings", [
            'headers' => $this->headers(),
            'json' => [
                'model' => $this->embeddingModel,
                'input' => array_values($texts),
            ],
        ]);

        $data = json_decode($response->getBody()->getContents(), true);

        return collect($data['data'])->sortBy('index')->pluck('embedding')->all();
    }

    public function name(): string
    {
        return 'http';
    }

    protected function headers(): array
    {
        return array_merge([
            'Authorization' => "Bearer {$this->apiKey}",
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ], $this->extraHeaders);
    }
}
