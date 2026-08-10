<?php

namespace Tests\Unit;

use App\Services\Ai\AiManager;
use Tests\TestCase;

class AiProviderTest extends TestCase
{
    public function test_groq_provider_is_available_without_network(): void
    {
        $provider = app(AiManager::class)->driver('groq');

        $this->assertSame('groq', $provider->name());
    }

    public function test_embeddings_use_the_configured_provider(): void
    {
        config(['ai.embedding_provider' => 'fake']);

        $embeddings = app(AiManager::class)->embed(['texto de prueba']);

        $this->assertCount(1, $embeddings);
        $this->assertCount(1536, $embeddings[0]);
    }
}
