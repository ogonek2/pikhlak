<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('car_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('car_categories')->nullOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->unsignedSmallInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['project_id', 'slug']);
        });

        Schema::create('cars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('car_categories')->nullOnDelete();
            $table->uuid('uuid')->unique();
            $table->string('vin')->nullable();
            $table->string('make');
            $table->string('model');
            $table->unsignedSmallInteger('year')->nullable();
            $table->decimal('price', 15, 2)->nullable();
            $table->string('currency', 3)->default('USD');
            $table->enum('status', ['draft', 'published', 'reserved', 'sold', 'archived'])->default('draft');
            $table->json('specs')->nullable();
            $table->string('import_source')->nullable();
            $table->string('external_id')->nullable();
            $table->text('description')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['project_id', 'status']);
            $table->index(['project_id', 'make', 'model', 'year']);
            $table->unique(['project_id', 'external_id']);
        });

        Schema::create('car_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('car_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['image', 'video'])->default('image');
            $table->string('path');
            $table->string('disk')->default('public');
            $table->unsignedSmallInteger('sort')->default(0);
            $table->string('alt')->nullable();
            $table->timestamps();
        });

        Schema::create('car_attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('car_id')->constrained()->cascadeOnDelete();
            $table->string('key');
            $table->string('value')->nullable();
            $table->timestamps();
            $table->unique(['car_id', 'key']);
        });

        Schema::create('car_import_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('source');
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->json('stats')->nullable();
            $table->string('file_path')->nullable();
            $table->text('error_message')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('car_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('car_id')->constrained()->cascadeOnDelete();
            $table->string('provider');
            $table->json('request')->nullable();
            $table->json('response')->nullable();
            $table->enum('status', ['success', 'failed'])->default('success');
            $table->timestamps();
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->foreign('car_interest_id')->references('id')->on('cars')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropForeign(['car_interest_id']);
        });
        Schema::dropIfExists('car_sync_logs');
        Schema::dropIfExists('car_import_jobs');
        Schema::dropIfExists('car_attributes');
        Schema::dropIfExists('car_media');
        Schema::dropIfExists('cars');
        Schema::dropIfExists('car_categories');
    }
};
