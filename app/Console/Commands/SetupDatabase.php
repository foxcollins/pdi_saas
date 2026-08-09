<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SetupDatabase extends Command
{
    protected $signature = 'app:setup-db';

    protected $description = 'Crea roles y permisos de base de datos para el multi-tenancy con RLS (requiere rol con BYPASSRLS / DDL).';

    public function handle(): int
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            $this->warn('Este comando solo aplica a PostgreSQL.');

            return self::SUCCESS;
        }

        $username = env('DB_RLS_USERNAME', 'app_tenant');
        $password = env('DB_RLS_PASSWORD', 'app_secret');

        $this->info('Creando rol de aplicación sin BYPASSRLS...');

        DB::statement(
            "DO \$\$ BEGIN IF NOT EXISTS (SELECT FROM pg_roles WHERE rolname = '{$username}') THEN CREATE ROLE {$username} LOGIN PASSWORD '{$password}'; END IF; END \$\$;"
        );

        $this->info('Concediendo permisos sobre tablas y secuencias...');

        DB::statement("GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA public TO {$username};");
        DB::statement("GRANT USAGE, SELECT ON ALL SEQUENCES IN SCHEMA public TO {$username};");
        DB::statement("ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON TABLES TO {$username};");
        DB::statement("ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT USAGE, SELECT ON SEQUENCES TO {$username};");

        $this->info('Listo. El rol '.$username.' puede acceder a los datos (sin BYPASSRLS).');

        return self::SUCCESS;
    }
}
