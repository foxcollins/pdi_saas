<?php

namespace App\Jobs;

use App\Models\Domain;
use App\Services\Site\DnsTxtVerifier;
use App\Support\TenantContext;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class VerifyDomainTxt implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(public Domain $domain)
    {
        $this->onQueue('domains');
    }

    public function handle(DnsTxtVerifier $verifier): void
    {
        TenantContext::set($this->domain->tenant_id);

        $token = $this->domain->verification_token;

        if (! $token) {
            $this->domain->update(['status' => 'pending', 'last_checked_at' => now()]);

            return;
        }

        $verified = $verifier->verifyTxt($this->domain->host, $token);

        $this->domain->update([
            'status' => $verified ? 'verified' : 'pending',
            'verified_at' => $verified ? now() : null,
            'last_checked_at' => now(),
        ]);
    }
}
