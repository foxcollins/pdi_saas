<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('price', 12, 2);
            $table->string('currency', 3)->default('USD');
            $table->string('unit')->nullable();
            $table->string('category')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['tenant_id', 'is_active']);
        });

        Schema::create('quotes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUuid('contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->foreignUuid('conversation_id')->nullable()->constrained('conversations')->nullOnDelete();
            $table->string('number');
            $table->string('status')->default('draft'); // draft | sent | accepted | rejected
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->jsonb('items')->default('[]');
            $table->text('notes')->nullable();
            $table->string('pdf_path')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->unique(['tenant_id', 'number']);
        });

        Schema::create('tool_runs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUuid('agent_id')->nullable()->constrained('agents')->nullOnDelete();
            $table->foreignUuid('conversation_id')->nullable()->constrained('conversations')->nullOnDelete();
            $table->string('tool');
            $table->jsonb('input')->default('{}');
            $table->jsonb('output')->default('{}');
            $table->string('status')->default('ok'); // ok | error
            $table->string('error')->nullable();
            $table->unsignedInteger('latency_ms')->default(0);
            $table->timestamps();

            $table->index(['tenant_id', 'tool']);
        });

        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            foreach (['products', 'quotes', 'tool_runs'] as $table) {
                DB::statement("ALTER TABLE {$table} ENABLE ROW LEVEL SECURITY");
                DB::statement(<<<SQL
                    CREATE POLICY {$table}_tenant_isolation
                      ON {$table}
                      USING (tenant_id = current_setting('app.tenant_id', true)::uuid)
                      WITH CHECK (tenant_id = current_setting('app.tenant_id', true)::uuid)
                SQL);
            }
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            foreach (['tool_runs', 'quotes', 'products'] as $table) {
                DB::statement("DROP POLICY IF EXISTS {$table}_tenant_isolation ON {$table}");
            }
        }

        Schema::dropIfExists('tool_runs');
        Schema::dropIfExists('quotes');
        Schema::dropIfExists('products');
    }
};
