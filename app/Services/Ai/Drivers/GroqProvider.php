<?php

namespace App\Services\Ai\Drivers;

class GroqProvider extends HttpProvider
{
    public function name(): string
    {
        return 'groq';
    }
}
