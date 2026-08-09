<?php

namespace App\Console\Commands;

use App\Jobs\VerifyDomainTxt;
use App\Models\Domain;
use Illuminate\Console\Command;

class VerifyPendingDomains extends Command
{
    protected $signature = 'domains:verify-pending';

    protected $description = 'Encola la verificación TXT de dominios aún no verificados.';

    public function handle(): int
    {
        $domains = Domain::query()
            ->where('status', '!=', 'verified')
            ->get();

        foreach ($domains as $domain) {
            VerifyDomainTxt::dispatch($domain);
        }

        $this->info("Se encolaron {$domains->count()} dominio(s) para verificación.");

        return self::SUCCESS;
    }
}
