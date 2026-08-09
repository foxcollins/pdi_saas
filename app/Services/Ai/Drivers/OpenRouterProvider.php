<?php

namespace App\Services\Ai\Drivers;

class OpenRouterProvider extends HttpProvider
{
    public function name(): string
    {
        return 'openrouter';
    }
}
