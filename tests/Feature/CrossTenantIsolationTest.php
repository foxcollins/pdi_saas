<?php

namespace Tests\Feature;

use App\Models\Website;
use Illuminate\Database\Connection;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class CrossTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_scope_de_aplicacion_filtra_por_tenant(): void
    {
        $a = $this->makeTenant('Tenant A', 'tenant-a');
        $b = $this->makeTenant('Tenant B', 'tenant-b');

        $this->switchTenant($a);
        Website::create(['name' => 'Sitio A', 'template' => 'minimal-business']);
        $this->assertSame(1, Website::count());

        $this->switchTenant($b);
        Website::create(['name' => 'Sitio B', 'template' => 'minimal-business']);
        $this->assertSame(1, Website::count());

        $this->switchTenant(null);
        $this->assertSame(0, Website::count());
    }

    public function test_el_scope_impide_crear_sin_contexto_de_tenant(): void
    {
        $this->switchTenant(null);

        $this->expectException(\RuntimeException::class);
        Website::create(['name' => 'Huérfano', 'template' => 'minimal-business']);
    }

    public function test_rls_bloquea_la_lectura_cross_tenant(): void
    {
        $conn = DB::connection('pgsql_rls');
        $a = $this->insertTenant($conn, 'Tenant RLS A', 'tenant-rls-a');
        $b = $this->insertTenant($conn, 'Tenant RLS B', 'tenant-rls-b');

        $conn->statement("select set_config('app.tenant_id', ?, false)", [$a]);
        $this->insertWebsite($conn, $a);
        $this->assertSame(1, $conn->table('websites')->count());

        $conn->statement("select set_config('app.tenant_id', ?, false)", [$b]);
        $this->assertSame(0, $conn->table('websites')->count());

        $conn->statement("select set_config('app.tenant_id', ?, false)", [$a]);
        $this->assertSame(1, $conn->table('websites')->count());

        $this->cleanupTenants([$a, $b]);
    }

    public function test_rls_bloquea_la_escritura_cross_tenant(): void
    {
        $conn = DB::connection('pgsql_rls');
        $a = $this->insertTenant($conn, 'Tenant RLS Write A', 'tenant-rls-wa');
        $b = $this->insertTenant($conn, 'Tenant RLS Write B', 'tenant-rls-wb');

        $conn->statement("select set_config('app.tenant_id', ?, false)", [$a]);
        $this->insertWebsite($conn, $a);

        $conn->statement("select set_config('app.tenant_id', ?, false)", [$b]);

        $updated = $conn->table('websites')->where('tenant_id', $a)->update(['name' => 'Hackeado']);
        $this->assertSame(0, $updated);

        try {
            $this->insertWebsite($conn, $a);
            $this->fail('RLS debería bloquear la inserción cross-tenant.');
        } catch (QueryException $e) {
            $this->assertStringContainsString('row-level security', strtolower($e->getMessage()));
        }

        $this->cleanupTenants([$a, $b]);
    }

    private function insertTenant(Connection $conn, string $name, string $slug): string
    {
        $id = (string) Str::uuid();

        $conn->table('tenants')->insert([
            'id' => $id,
            'name' => $name,
            'slug' => $slug,
            'industry' => null,
            'country' => null,
            'plan_id' => null,
            'status' => 'active',
            'settings' => '{}',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $id;
    }

    private function insertWebsite(Connection $conn, string $tenantId): void
    {
        $conn->table('websites')->insert([
            'id' => (string) Str::uuid(),
            'tenant_id' => $tenantId,
            'name' => 'Sitio '.$tenantId,
            'template' => 'minimal-business',
            'theme' => '{}',
            'pages' => '[]',
            'status' => 'draft',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function cleanupTenants(array $tenantIds): void
    {
        DB::table('tenants')->whereIn('id', $tenantIds)->delete();
    }
}
