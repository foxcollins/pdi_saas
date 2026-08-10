<?php

namespace App\Services\Tools;

use App\Services\Tools\Drivers\CatalogLookupTool;
use App\Services\Tools\Drivers\CreateLeadTool;
use App\Services\Tools\Drivers\CreateQuoteTool;
use App\Services\Tools\Drivers\CreateTaskTool;
use App\Services\Tools\Drivers\N8nWebhookTool;
use App\Services\Tools\Drivers\NotifyHumanTool;
use App\Services\Tools\Drivers\QuoteCalculatorTool;

class ToolManager extends ToolRunner
{
    public function __construct()
    {
        $this->register('catalog_lookup', CatalogLookupTool::class);
        $this->register('quote_calculator', QuoteCalculatorTool::class);
        $this->register('create_quote', CreateQuoteTool::class);
        $this->register('create_lead', CreateLeadTool::class);
        $this->register('create_task', CreateTaskTool::class);
        $this->register('n8n_webhook', N8nWebhookTool::class);
        $this->register('notify_human', NotifyHumanTool::class);
    }

    public function catalog(): array
    {
        return collect($this->registry)
            ->map(fn ($class) => (new $class)->definition())
            ->values()
            ->all();
    }

    public function definition(string $name): array
    {
        return $this->resolve($name)->definition();
    }
}
