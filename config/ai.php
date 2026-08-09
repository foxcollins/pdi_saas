<?php

return [
    'default_provider' => env('AI_DEFAULT_PROVIDER', 'fake'),

    'providers' => [
        'openrouter' => [
            'base_url' => 'https://openrouter.ai/api/v1',
            'api_key' => env('OPENROUTER_API_KEY'),
            'chat_model' => env('AI_CHAT_MODEL', 'openai/gpt-4o-mini'),
            'fast_model' => env('AI_FAST_MODEL', 'openai/gpt-4o-mini'),
            'embedding_model' => env('AI_EMBEDDING_MODEL', 'openai/text-embedding-3-small'),
            'embedding_dimensions' => 1536,
            'http_headers' => ['HTTP-Referer' => env('APP_URL'), 'X-Title' => 'PDI SAAS'],
        ],
        'openai' => [
            'base_url' => 'https://api.openai.com/v1',
            'api_key' => env('OPENAI_API_KEY'),
            'chat_model' => env('AI_CHAT_MODEL', 'gpt-4o-mini'),
            'fast_model' => env('AI_FAST_MODEL', 'gpt-4o-mini'),
            'embedding_model' => env('AI_EMBEDDING_MODEL', 'text-embedding-3-small'),
            'embedding_dimensions' => 1536,
            'http_headers' => [],
        ],
        'fake' => [
            'embedding_dimensions' => 1536,
        ],
    ],

    'confidence_threshold' => env('AI_CONFIDENCE_THRESHOLD', 0.16),

    'retrieval_k' => env('AI_RETRIEVAL_K', 5),

    'prices_per_1m' => [
        'openai/text-embedding-3-small' => ['in' => 0.02, 'out' => 0.0],
        'openai/gpt-4o-mini' => ['in' => 0.15, 'out' => 0.6],
        'text-embedding-3-small' => ['in' => 0.02, 'out' => 0.0],
        'gpt-4o-mini' => ['in' => 0.15, 'out' => 0.6],
    ],

    'default_prices' => ['in' => 0.5, 'out' => 1.5],

    'timeout' => 30,
];
