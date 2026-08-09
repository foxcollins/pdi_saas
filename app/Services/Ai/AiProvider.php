<?php

namespace App\Services\Ai;

interface AiProvider
{
    public function chat(array $messages, array $options = []): string;

    public function chatStream(array $messages, callable $onChunk, array $options = []): void;

    public function embed(array $texts): array;

    public function name(): string;
}
