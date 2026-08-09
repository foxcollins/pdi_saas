<?php

namespace Tests\Feature;

use App\Models\Domain;
use App\Services\Site\DomainResolver;
use App\Services\Site\WebsiteBuilderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DomainResolutionTest extends TestCase
{
    use RefreshDatabase;

    public function test_resuelve_subdominio_de_la_plataforma_por_slug(): void
    {
        $tenant = $this->makeTenant('Andina Resolver', 'andina-resolver', 'Hidráulica');

        $resolved = app(DomainResolver::class)->resolve('andina-resolver.pdi_saas.test');

        $this->assertNotNull($resolved);
        $this->assertSame($tenant->id, $resolved->id);
    }

    public function test_resuelve_dominio_personalizado_verificado_ignorando_www(): void
    {
        $tenant = $this->makeTenant('Empresa Dominio', 'empresa-dominio');

        Domain::create([
            'tenant_id' => $tenant->id,
            'host' => 'empresa.test',
            'is_primary' => true,
            'status' => 'verified',
            'verified_at' => now(),
        ]);

        $resolved = app(DomainResolver::class)->resolve('www.empresa.test');

        $this->assertNotNull($resolved);
        $this->assertSame($tenant->id, $resolved->id);
    }

    public function test_ignora_dominios_no_verificados_y_hosts_invalidos(): void
    {
        $tenant = $this->makeTenant('Empresa No Verify', 'empresa-no-verify');

        Domain::create([
            'tenant_id' => $tenant->id,
            'host' => 'pendiente.test',
            'is_primary' => false,
            'status' => 'pending',
        ]);

        $this->assertNull(app(DomainResolver::class)->resolve('pendiente.test'));
        $this->assertNull(app(DomainResolver::class)->resolve('localhost'));
        $this->assertNull(app(DomainResolver::class)->resolve('desconocido.test'));
    }

    public function test_get_root_sirve_el_sitio_del_tenant_por_subdominio_plataforma(): void
    {
        $tenant = $this->makeTenant('Andina Web', 'andina-web', 'Hidráulica');
        $this->switchTenant($tenant);
        app(WebsiteBuilderService::class)->createSite($tenant, 'minimal-business', 'Andina Web');

        $this->get('http://andina-web.pdi_saas.test/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('PublicSite'));
    }

    public function test_get_root_sirve_el_sitio_por_dominio_personalizado(): void
    {
        $tenant = $this->makeTenant('Empresa Custom', 'empresa-custom');
        $this->switchTenant($tenant);
        app(WebsiteBuilderService::class)->createSite($tenant, 'minimal-business', 'Empresa Custom');

        Domain::create([
            'tenant_id' => $tenant->id,
            'host' => 'www.customsite.test',
            'is_primary' => true,
            'status' => 'verified',
            'verified_at' => now(),
        ]);

        $this->get('http://customsite.test/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('PublicSite'));
    }

    public function test_get_root_sin_host_resoluble_muestra_la_landing(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Landing'));
    }
}
