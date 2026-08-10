<?php

return [

    'channels' => [
        'whatsapp' => [
            'label' => 'WhatsApp',
            'provider' => 'meta',
            'driver' => 'whatsapp',
            'icon' => 'M17.5 14.5c-.4-.2-2.2-1.1-2.5-1.2s-.6-.2-.8.2-1 1.2-1.2 1.5-.4.3-.8.1a9.5 9.5 0 0 1-3.1-1.9A7.2 7.2 0 0 1 7 9.5c0-.4.2-.6.4-.7s.5-.5.7-.8.2-.4.1-.7-1-2.4-1.3-3.2-.7-.7-.9-.7h-.8c-.2 0-.6.1-.9.4A3.9 3.9 0 0 0 3.2 7a6.8 6.8 0 0 0 1.4 3.9 12.6 12.6 0 0 0 5.4 4.6 9.2 9.2 0 0 0 3.4.8c1.6 0 2.6-.8 2.9-1.6s.3-1.5.2-1.7z',
            'fields' => [
                'phone_number_id' => ['label' => 'Phone Number ID', 'type' => 'text', 'required' => true],
                'access_token' => ['label' => 'Access Token', 'type' => 'password', 'required' => true],
                'waba_id' => ['label' => 'WABA ID', 'type' => 'text', 'required' => false],
            ],
            'meta' => [
                'base_url' => 'https://graph.facebook.com/v21.0',
            ],
            'max_conversations' => 5000,
            'window_hours' => 24,
        ],
        'messenger' => [
            'label' => 'Messenger',
            'provider' => 'meta',
            'driver' => 'messenger',
            'icon' => 'M4 4h16v11H9l-5 4V4zM7 8h10M7 11h6',
            'fields' => [
                'page_id' => ['label' => 'Page ID', 'type' => 'text', 'required' => true],
                'page_access_token' => ['label' => 'Page Access Token', 'type' => 'password', 'required' => true],
            ],
            'meta' => [
                'base_url' => 'https://graph.facebook.com/v21.0',
            ],
            'max_conversations' => 5000,
            'window_hours' => 24,
        ],
        'instagram' => [
            'label' => 'Instagram',
            'provider' => 'meta',
            'driver' => 'messenger',
            'icon' => 'M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8zm0 2a2 2 0 1 1 0 4 2 2 0 0 1 0-4zM8 3h8a5 5 0 0 1 5 5v8a5 5 0 0 1-5 5H8a5 5 0 0 1-5-5V8a5 5 0 0 1 5-5z',
            'fields' => [
                'instagram_user_id' => ['label' => 'Instagram Business Account ID', 'type' => 'text', 'required' => true],
                'access_token' => ['label' => 'Access Token', 'type' => 'password', 'required' => true],
            ],
            'meta' => [
                'base_url' => 'https://graph.facebook.com/v21.0',
            ],
            'max_conversations' => 5000,
            'window_hours' => 24,
        ],
        'telegram' => [
            'label' => 'Telegram',
            'provider' => 'telegram',
            'driver' => 'telegram',
            'icon' => 'M9 12l-1.5 5.5L17 9 9 12zm0 0l3-1 4-3',
            'fields' => [
                'bot_token' => ['label' => 'Bot Token', 'type' => 'password', 'required' => true],
                'bot_username' => ['label' => 'Username del bot (sin @)', 'type' => 'text', 'required' => false],
            ],
            'meta' => [
                'base_url' => 'https://api.telegram.org/bot',
            ],
            'max_conversations' => 5000,
            'window_hours' => null,
        ],
        'email' => [
            'label' => 'Email',
            'provider' => 'smtp',
            'driver' => 'email',
            'icon' => 'M4 5h16a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1zm0 2l8 6 8-6',
            'fields' => [
                'from_address' => ['label' => 'Dirección de entrada', 'type' => 'email', 'required' => true],
                'forward_secret' => ['label' => 'Secreto de forward', 'type' => 'password', 'required' => false],
            ],
            'meta' => [
                'base_url' => null,
            ],
            'max_conversations' => 5000,
            'window_hours' => null,
        ],
    ],

    'webhook_path' => 'webhooks',

    'testing' => false,

];
