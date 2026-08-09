<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tenantTables = [
        'tenant_user',
        'business_profiles',
        'websites',
        'media',
        'knowledge_sources',
        'knowledge_documents',
        'knowledge_chunks',
        'contacts',
        'conversations',
        'messages',
        'leads',
        'customer_memory',
        'ai_runs',
        'agents',
        'integrations',
        'webhook_outbox',
        'subscriptions',
        'analytics_events',
    ];

    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('CREATE EXTENSION IF NOT EXISTS vector');
        }

        foreach ($this->tenantTables as $table) {
            if ($driver === 'pgsql') {
                DB::statement("ALTER TABLE {$table} ENABLE ROW LEVEL SECURITY");
                DB::statement(<<<SQL
                    CREATE POLICY {$table}_tenant_isolation
                      ON {$table}
                      USING (tenant_id = current_setting('app.tenant_id', true)::uuid)
                      WITH CHECK (tenant_id = current_setting('app.tenant_id', true)::uuid)
                SQL);
            }
        }

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE domains DISABLE ROW LEVEL SECURITY');

            DB::statement('CREATE INDEX knowledge_chunks_embedding_hnsw ON knowledge_chunks USING hnsw (embedding vector_cosine_ops)');
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver !== 'pgsql') {
            return;
        }

        foreach ($this->tenantTables as $table) {
            if (Schema::hasTable($table)) {
                DB::statement("DROP POLICY IF EXISTS {$table}_tenant_isolation ON {$table}");
                DB::statement("ALTER TABLE {$table} DISABLE ROW LEVEL SECURITY");
            }
        }

        DB::statement('DROP INDEX IF EXISTS knowledge_chunks_embedding_hnsw');
    }
};
