<?php

namespace Tests\Feature;

use App\Jobs\VerifyDomainTxt;
use App\Models\Domain;
use App\Models\User;
use App\Services\Site\DnsTxtVerifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class DomainVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_verify_txt_encuentra_el_token_en_el_registro_por_doh(): void
    {
        $token = str_repeat('a', 64);

        Http::fake([
            'cloudflare-dns.com/*' => Http::response([
                'Status' => 0,
                'Answer' => [
                    ['name' => '_pdi-verify.empresa.test', 'type' => 16, 'data' => "\"pdi-verify={$token}\""],
                ],
            ]),
        ]);

        $verifier = new DnsTxtVerifier;

        $this->assertTrue($verifier->verifyTxt('empresa.test', $token));
    }

    public function test_verify_txt_no_encuentra_token_si_el_registro_no_existe(): void
    {
        Http::fake([
            'cloudflare-dns.com/*' => Http::response(['Status' => 3, 'Answer' => []]),
        ]);

        $verifier = new DnsTxtVerifier;

        $this->assertFalse($verifier->verifyTxt('empresa.test', str_repeat('x', 64)));
    }

    public function test_verify_txt_rechaza_valores_que_no_coinciden(): void
    {
        Http::fake([
            'cloudflare-dns.com/*' => Http::response([
                'Status' => 0,
                'Answer' => [
                    ['name' => '_pdi-verify.empresa.test', 'type' => 16, 'data' => '"pdi-verify=otro-valor"'],
                ],
            ]),
        ]);

        $verifier = new DnsTxtVerifier;

        $this->assertFalse($verifier->verifyTxt('empresa.test', str_repeat('b', 64)));
    }

    public function test_record_name_usa_prefijo_fijo_no_el_host(): void
    {
        $verifier = new DnsTxtVerifier;

        $this->assertSame('_pdi-verify.www.empresa.test', $verifier->recordName('www.empresa.test'));
    }

    public function test_job_marca_verificado_cuando_el_txt_coincide(): void
    {
        $tenant = $this->makeTenant('Verify Job', 'verify-job');

        $domain = Domain::create([
            'tenant_id' => $tenant->id,
            'host' => 'empresa.test',
            'status' => 'pending',
            'verification_token' => str_repeat('c', 64),
        ]);

        $verifier = $this->createMock(DnsTxtVerifier::class);
        $verifier->method('verifyTxt')->willReturn(true);

        (new VerifyDomainTxt($domain))->handle($verifier);

        $domain->refresh();

        $this->assertSame('verified', $domain->status);
        $this->assertNotNull($domain->verified_at);
        $this->assertNotNull($domain->last_checked_at);
    }

    public function test_job_mantiene_pendiente_cuando_el_txt_no_coincide(): void
    {
        $tenant = $this->makeTenant('Verify Pending', 'verify-pending');

        $domain = Domain::create([
            'tenant_id' => $tenant->id,
            'host' => 'empresa.test',
            'status' => 'pending',
            'verification_token' => str_repeat('d', 64),
        ]);

        $verifier = $this->createMock(DnsTxtVerifier::class);
        $verifier->method('verifyTxt')->willReturn(false);

        (new VerifyDomainTxt($domain))->handle($verifier);

        $domain->refresh();

        $this->assertSame('pending', $domain->status);
        $this->assertNull($domain->verified_at);
        $this->assertNotNull($domain->last_checked_at);
    }

    public function test_store_genera_token_aleatorio_y_encola_verificacion(): void
    {
        Queue::fake();

        $tenant = $this->makeTenant('Verify Store', 'verify-store');
        $this->switchTenant($tenant);
        $user = User::factory()->create();
        $tenant->users()->attach($user->id, ['role' => 'owner']);
        $this->actingAs($user)->withSession(['current_tenant_id' => $tenant->id]);

        $this->post('/app/domains', ['host' => 'nuevo.test'])
            ->assertRedirect();

        $domain = Domain::where('host', 'nuevo.test')->firstOrFail();

        $this->assertNotNull($domain->verification_token);
        $this->assertSame('pending', $domain->status);
        $this->assertSame($tenant->id, $domain->tenant_id);

        Queue::assertPushed(VerifyDomainTxt::class);
    }

    public function test_verify_encola_verificacion_del_dominio_del_tenant(): void
    {
        Queue::fake();

        $tenant = $this->makeTenant('Verify Recheck', 'verify-recheck');
        $this->switchTenant($tenant);
        $user = User::factory()->create();
        $tenant->users()->attach($user->id, ['role' => 'owner']);
        $this->actingAs($user)->withSession(['current_tenant_id' => $tenant->id]);

        $domain = Domain::create([
            'tenant_id' => $tenant->id,
            'host' => 'recheck.test',
            'status' => 'pending',
            'verification_token' => str_repeat('e', 64),
        ]);

        $this->post("/app/domains/{$domain->id}/verify")
            ->assertRedirect();

        Queue::assertPushed(VerifyDomainTxt::class, fn ($job) => $job->domain->is($domain));
    }

    public function test_tenant_no_puede_verificar_dominio_de_otro_tenant(): void
    {
        Queue::fake();

        $tenantA = $this->makeTenant('Verify A', 'verify-a');
        $tenantB = $this->makeTenant('Verify B', 'verify-b');

        $domain = Domain::create([
            'tenant_id' => $tenantA->id,
            'host' => 'ajena.test',
            'status' => 'pending',
            'verification_token' => str_repeat('f', 64),
        ]);

        $this->switchTenant($tenantB);
        $user = User::factory()->create();
        $tenantB->users()->attach($user->id, ['role' => 'owner']);
        $this->actingAs($user)->withSession(['current_tenant_id' => $tenantB->id]);

        $this->post("/app/domains/{$domain->id}/verify")
            ->assertNotFound();

        Queue::assertNotPushed(VerifyDomainTxt::class);
    }
}
