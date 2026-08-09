<?php

namespace App\Jobs;

use App\Models\KnowledgeDocument;
use App\Services\Knowledge\KnowledgePipelineService;
use App\Support\TenantContext;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessKnowledgeDocument implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public KnowledgeDocument $document)
    {
        $this->onQueue('knowledge');
    }

    public function handle(KnowledgePipelineService $pipeline): void
    {
        TenantContext::set($this->document->tenant_id);

        $pipeline->process($this->document);
    }
}
