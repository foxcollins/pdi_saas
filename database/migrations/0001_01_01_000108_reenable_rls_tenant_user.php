<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql' || ! Schema::hasTable('tenant_user')) {
            return;
        }

        // 000107 reconstruyó tenant_user y perdió la RLS que 000106 había aplicado.
        DB::statement('ALTER TABLE tenant_user ENABLE ROW LEVEL SECURITY');
        DB::statement('DROP POLICY IF EXISTS tenant_user_tenant_isolation ON tenant_user');
        DB::statement(<<<'SQL'
            CREATE POLICY tenant_user_tenant_isolation
              ON tenant_user
              USING (tenant_id = current_setting('app.tenant_id', true)::uuid)
              WITH CHECK (tenant_id = current_setting('app.tenant_id', true)::uuid)
        SQL);
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql' || ! Schema::hasTable('tenant_user')) {
            return;
        }

        DB::statement('DROP POLICY IF EXISTS tenant_user_tenant_isolation ON tenant_user');
        DB::statement('ALTER TABLE tenant_user DISABLE ROW LEVEL SECURITY');
    }
};
