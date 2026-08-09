<?php

namespace App\Services\Ai\Drivers;

class OpenAiProvider extends HttpProvider
{
    public function name(): string
    {
        return 'openai';
    }
}
