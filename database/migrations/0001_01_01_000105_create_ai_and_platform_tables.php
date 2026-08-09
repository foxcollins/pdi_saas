<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_runs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('trigger')->default('chat');
            $table->string('model_profile_id')->nullable();
            $table->integer('tokens_in')->default(0);
            $table->integer('tokens_out')->default(0);
            $table->decimal('cost_usd', 12, 8)->default(0);
            $table->integer('latency_ms')->default(0);
            $table->boolean('cached')->default(false);
            $table->timestamps();

            $table->index(['tenant_id', 'created_at']);
        });

        Schema::create('agents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('slug')->default('assistant');
            $table->string('name')->default('Asistente');
            $table->text('instructions')->nullable();
            $table->jsonb('tools')->default('[]');
            $table->string('model_profile_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->jsonb('guardrails')->default('{}');
            $table->timestamps();

            $table->index(['tenant_id', 'slug']);
        });

        Schema::create('integrations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('channel'); // whatsapp | instagram | facebook | email | n8n | openrouter
            $table->string('provider')->nullable();
            $table->text('config_encrypted')->nullable();
            $table->string('status')->default('disabled');
            $table->string('webhook_secret')->nullable();
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'channel']);
        });

        Schema::create('webhook_outbox', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('event');
            $table->jsonb('payload')->default('{}');
            $table->string('status')->default('pending');
            $table->integer('attempts')->default(0);
            $table->timestamp('next_attempt_at')->nullable();
            $table->integer('response_code')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUuid('plan_id')->constrained('plans')->cascadeOnDelete();
            $table->string('status')->default('active');
            $table->timestamp('current_period_start')->nullable();
            $table->timestamp('current_period_end')->nullable();
            $table->string('provider_ref')->nullable();
            $table->jsonb('ai_usage_billed')->default('{}');
            $table->timestamps();
        });

        Schema::create('analytics_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('kind'); // page_view | chat_message | lead_generated | contact_form
            $table->jsonb('context')->default('{}');
            $table->timestamps();

            $table->index(['tenant_id', 'kind', 'created_at']);
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->uuid('tenant_id')->nullable();
            $table->string('action');
            $table->string('entity')->nullable();
            $table->jsonb('before')->nullable();
            $table->jsonb('after')->nullable();
            $table->string('ip')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('analytics_events');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('webhook_outbox');
        Schema::dropIfExists('integrations');
        Schema::dropIfExists('agents');
        Schema::dropIfExists('ai_runs');
    }
};
