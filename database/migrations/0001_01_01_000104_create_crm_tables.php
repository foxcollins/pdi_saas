<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('whatsapp_id')->nullable();
            $table->string('instagram_username')->nullable();
            $table->jsonb('tags')->default('[]');
            $table->string('lifecycle')->default('lead');
            $table->string('consent_status')->default('unknown');
            $table->jsonb('memory_summary')->default('{}');
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'created_at']);
        });

        Schema::create('conversations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUuid('contact_id')->constrained('contacts')->cascadeOnDelete();
            $table->string('channel')->default('web');
            $table->string('external_channel_id')->nullable();
            $table->string('subject')->nullable();
            $table->string('status')->default('open');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUuid('conversation_id')->constrained('conversations')->cascadeOnDelete();
            $table->string('direction'); // in | out
            $table->string('author_type')->default('visitor'); // visitor | agent | human | system
            $table->text('content');
            $table->jsonb('attachments')->default('[]');
            $table->timestamps();

            $table->index(['tenant_id', 'conversation_id', 'created_at']);
        });

        Schema::create('leads', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUuid('contact_id')->constrained('contacts')->cascadeOnDelete();
            $table->string('source_channel')->default('web');
            $table->string('intent')->nullable();
            $table->integer('lead_score')->default(0);
            $table->string('status')->default('new'); // new | qualified | negotiation | won | lost
            $table->string('next_action')->nullable();
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });

        Schema::create('customer_memory', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUuid('contact_id')->constrained('contacts')->cascadeOnDelete();
            $table->string('kind'); // summary | preferences | interests | state
            $table->jsonb('content')->default('{}');
            $table->timestamp('window_start')->nullable();
            $table->timestamp('window_end')->nullable();
            $table->string('policy')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'contact_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_memory');
        Schema::dropIfExists('leads');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('conversations');
        Schema::dropIfExists('contacts');
    }
};
