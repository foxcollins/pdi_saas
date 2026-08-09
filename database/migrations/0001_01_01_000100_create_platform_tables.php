<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->decimal('price_monthly', 10, 2)->default(0);
            $table->jsonb('limits')->default('{}');
            $table->timestamps();
        });

        Schema::create('tenants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('industry')->nullable();
            $table->string('country')->nullable();
            $table->foreignUuid('plan_id')->nullable()->constrained('plans')->nullOnDelete();
            $table->string('status')->default('active');
            $table->jsonb('settings')->default('{}');
            $table->timestamps();
        });

        Schema::create('tenant_user', function (Blueprint $table) {
            $table->uuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role')->default('owner');
            $table->timestamps();

            $table->primary(['tenant_id', 'user_id']);
        });

        Schema::create('domains', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('host')->unique();
            $table->boolean('is_primary')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('domains');
        Schema::dropIfExists('tenant_user');
        Schema::dropIfExists('tenants');
        Schema::dropIfExists('plans');
    }
};
