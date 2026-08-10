<?php

namespace App\Services\Channels;

use GuzzleHttp\Client;
use Illuminate\Http\Request;

abstract class BaseHttpDriver implements ChannelDriver
{
    protected array $config;

    protected ?string $webhookSecret;

    public function __construct(array $config = [], ?string $webhookSecret = null)
    {
        $this->config = $config;
        $this->webhookSecret = $webhookSecret;
    }

    public function name(): string
    {
        return class_basename($this);
    }

    protected function client(array $options = []): Client
    {
        return new Client(array_merge(['timeout' => 30], $options));
    }

    protected function hmacValid(Request $request, string $header): bool
    {
        $signature = $request->header($header, '');
        $payload = $request->getContent();

        if (! $this->webhookSecret || $signature === '') {
            return false;
        }

        $expected = 'sha256='.hash_hmac('sha256', $payload, $this->webhookSecret);

        return hash_equals($expected, $signature);
    }

    protected function config(string $key, $default = null)
    {
        return $this->config[$key] ?? $default;
    }
}
