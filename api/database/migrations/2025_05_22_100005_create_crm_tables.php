<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->string('name');
            $table->unsignedSmallInteger('sort')->default(0);
            $table->string('color', 20)->nullable();
            $table->boolean('is_won')->default(false);
            $table->boolean('is_lost')->default(false);
            $table->timestamps();
            $table->unique(['project_id', 'code']);
        });

        Schema::create('managers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('capacity')->default(50);
            $table->boolean('is_available')->default(true);
            $table->timestamps();
            $table->unique(['user_id', 'project_id']);
        });

        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->uuid('uuid')->unique();
            $table->foreignId('chat_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('telegram_user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('status_id')->nullable()->constrained('lead_statuses')->nullOnDelete();
            $table->foreignId('assigned_manager_id')->nullable()->constrained('managers')->nullOnDelete();
            $table->unsignedSmallInteger('warming_score')->default(0);
            $table->string('source')->nullable();
            $table->unsignedBigInteger('car_interest_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('last_contacted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['project_id', 'status_id', 'warming_score']);
            $table->index(['assigned_manager_id']);
        });

        Schema::create('lead_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('body');
            $table->boolean('is_pinned')->default(false);
            $table->timestamps();
            $table->index(['lead_id', 'created_at']);
        });

        Schema::create('lead_communications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->enum('channel', ['telegram', 'phone', 'email', 'admin', 'ai', 'other'])->default('telegram');
            $table->string('type');
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('summary')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->index(['lead_id', 'created_at']);
        });

        Schema::create('lead_pipeline_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_status_id')->nullable()->constrained('lead_statuses')->nullOnDelete();
            $table->foreignId('to_status_id')->nullable()->constrained('lead_statuses')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reason')->nullable();
            $table->timestamps();
        });

        Schema::create('lead_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('score');
            $table->json('factors')->nullable();
            $table->timestamp('calculated_at');
            $table->timestamps();
            $table->index(['lead_id', 'calculated_at']);
        });

        Schema::create('lead_automation_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('trigger');
            $table->json('conditions')->nullable();
            $table->json('actions');
            $table->unsignedSmallInteger('priority')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('lead_automation_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rule_id')->constrained('lead_automation_rules')->cascadeOnDelete();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['pending', 'running', 'completed', 'failed'])->default('pending');
            $table->json('result')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_automation_runs');
        Schema::dropIfExists('lead_automation_rules');
        Schema::dropIfExists('lead_scores');
        Schema::dropIfExists('lead_pipeline_history');
        Schema::dropIfExists('lead_communications');
        Schema::dropIfExists('lead_notes');
        Schema::dropIfExists('leads');
        Schema::dropIfExists('managers');
        Schema::dropIfExists('lead_statuses');
    }
};
