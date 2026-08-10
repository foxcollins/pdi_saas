<?php

namespace App\Services\Tools;

use App\Models\Agent;
use App\Models\Conversation;
use App\Models\Tenant;

class ToolContext
{
    public function __construct(
        public Tenant $tenant,
        public ?Agent $agent = null,
        public ?Conversation $conversation = null,
        public array $visitor = [],
        public ?array $contact = null,
    ) {}
}
