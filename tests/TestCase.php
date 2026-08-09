<?php

namespace Tests;

use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Str;

abstract class TestCase extends BaseTestCase
{
    private static bool $dbRolesConfigured = false;

    public function createApplication(): Application
    {
        $app = parent::createApplication();

        $this->configureDatabaseRolesOnce($app);

        return $app;
    }

    protected function makeTenant(string $name = 'Empresa Demo', ?string $slug = null, ?string $industry = 'Servicios'): Tenant
    {
        return Tenant::create([
            'name' => $name,
            'slug' => $slug ?: Str::slug($name).'-'.Str::lower(Str::random(6)),
            'industry' => $industry,
            'status' => 'active',
        ]);
    }

    protected function switchTenant(?Tenant $tenant): void
    {
        TenantContext::set($tenant?->id);
    }

    private function configureDatabaseRolesOnce(Application $app): void
    {
        if (self::$dbRolesConfigured) {
            return;
        }

        self::$dbRolesConfigured = true;

        if ($app['config']->get('database.default') !== 'pgsql') {
            return;
        }

        $cfg = $app['config']->get('database.connections.pgsql');
        $rls = $app['config']->get('database.connections.pgsql_rls');

        $pdo = new \PDO(
            "pgsql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['database']}",
            $cfg['username'],
            $cfg['password']
        );

        $username = $rls['username'];
        $password = $rls['password'];

        $pdo->exec("DO \$\$ BEGIN IF NOT EXISTS (SELECT FROM pg_roles WHERE rolname = '{$username}') THEN CREATE ROLE {$username} LOGIN PASSWORD '{$password}'; END IF; END \$\$;");
        $pdo->exec("GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA public TO {$username};");
        $pdo->exec("GRANT USAGE, SELECT ON ALL SEQUENCES IN SCHEMA public TO {$username};");
        $pdo->exec("ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON TABLES TO {$username};");
        $pdo->exec("ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT USAGE, SELECT ON SEQUENCES TO {$username};");
    }
}
