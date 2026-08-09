<?php

namespace App\Console\Commands;

use App\Services\Memory\MemoryService;
use Illuminate\Console\Command;

class PruneMemory extends Command
{
    protected $signature = 'memory:prune {--days= : D\u00edas de retenci\u00f3n}';

    protected $description = 'Elimina registros de memoria de cliente vencidos seg\u00fan la pol\u00edtica de retenci\u00f3n.';

    public function handle(MemoryService $memory): int
    {
        $pruned = $memory->pruneExpired($this->option('days') ? (int) $this->option('days') : null);

        $this->info("Memoria podada: {$pruned} registros eliminados.");

        return self::SUCCESS;
    }
}
