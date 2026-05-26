<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->json('settings')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('bots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->text('telegram_token')->nullable();
            $table->string('webhook_secret')->nullable();
            $table->string('api_key_hash', 64)->nullable();
            $table->enum('mode', ['webhook', 'polling'])->default('webhook');
            $table->json('config')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['project_id', 'is_active']);
        });

        Schema::create('bot_webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bot_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('telegram_update_id');
            $table->json('payload');
            $table->enum('status', ['received', 'processing', 'processed', 'failed', 'duplicate'])->default('received');
            $table->text('error_message')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->unique(['bot_id', 'telegram_update_id']);
            $table->index(['bot_id', 'status', 'created_at']);
        });

        Schema::create('project_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role')->default('member');
            $table->timestamps();
            $table->unique(['project_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_user');
        Schema::dropIfExists('bot_webhook_logs');
        Schema::dropIfExists('bots');
        Schema::dropIfExists('projects');
    }
};
