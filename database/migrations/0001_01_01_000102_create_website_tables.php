<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name');
            $table->string('tagline')->nullable();
            $table->text('description')->nullable();
            $table->string('logo_url')->nullable();
            $table->string('industry')->nullable();
            $table->jsonb('services')->default('[]');
            $table->jsonb('products')->default('[]');
            $table->jsonb('branches')->default('[]');
            $table->jsonb('schedule')->default('[]');
            $table->jsonb('contact')->default('{}');
            $table->jsonb('social')->default('{}');
            $table->jsonb('faqs')->default('[]');
            $table->jsonb('team')->default('[]');
            $table->jsonb('certifications')->default('[]');
            $table->timestamps();

            $table->index(['tenant_id', 'created_at']);
        });

        Schema::create('websites', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name')->default('Mi sitio');
            $table->string('template')->default('modern-tech');
            $table->jsonb('theme')->default('{}');
            $table->jsonb('pages')->default('[]');
            $table->string('status')->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });

        Schema::create('media', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('file_key')->nullable();
            $table->string('url');
            $table->string('mime')->nullable();
            $table->bigInteger('size')->default(0);
            $table->string('alt')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media');
        Schema::dropIfExists('websites');
        Schema::dropIfExists('business_profiles');
    }
};
