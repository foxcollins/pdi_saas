<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement('CREATE EXTENSION IF NOT EXISTS vector');
        }

        Schema::create('knowledge_sources', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('type'); // upload | url | faq | manual
            $table->string('title');
            $table->string('status')->default('pending');
            $table->jsonb('meta')->default('{}');
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });

        Schema::create('knowledge_documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUuid('source_id')->constrained('knowledge_sources')->cascadeOnDelete();
            $table->string('filename');
            $table->string('mime')->nullable();
            $table->string('storage_key')->nullable();
            $table->string('status')->default('pending'); // pending | processing | ready | error
            $table->integer('chunk_count')->default(0);
            $table->string('embedding_model')->nullable();
            $table->integer('embedding_dimensions')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });

        Schema::create('knowledge_chunks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUuid('document_id')->constrained('knowledge_documents')->cascadeOnDelete();
            $table->integer('chunk_index')->default(0);
            $table->text('content');
            $table->integer('token_count')->default(0);
            $table->string('source_ref')->nullable();
            $table->rawColumn('embedding', 'vector(1536)')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'document_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_chunks');
        Schema::dropIfExists('knowledge_documents');
        Schema::dropIfExists('knowledge_sources');
    }
};
