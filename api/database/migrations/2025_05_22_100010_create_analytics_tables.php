<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('event_type');
            $table->string('entity_type')->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();
            $table->index(['project_id', 'event_type', 'occurred_at']);
            $table->index(['entity_type', 'entity_id']);
        });

        Schema::create('analytics_dialog_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->unsignedInteger('messages_count')->default(0);
            $table->decimal('ai_ratio', 5, 4)->default(0);
            $table->json('intents')->nullable();
            $table->timestamps();
            $table->unique(['chat_id', 'date']);
        });

        Schema::create('analytics_intents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_id')->constrained()->cascadeOnDelete();
            $table->foreignId('message_id')->nullable()->constrained()->nullOnDelete();
            $table->string('intent');
            $table->decimal('confidence', 5, 4)->nullable();
            $table->timestamps();
            $table->index(['chat_id', 'intent', 'created_at']);
        });

        Schema::create('analytics_hot_leads_daily', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('score');
            $table->timestamps();
            $table->unique(['project_id', 'date', 'lead_id']);
        });

        Schema::create('analytics_ai_effectiveness', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->foreignId('profile_id')->constrained('ai_profiles')->cascadeOnDelete();
            $table->unsignedInteger('total_replies')->default(0);
            $table->decimal('resolved_rate', 5, 4)->default(0);
            $table->decimal('escalation_rate', 5, 4)->default(0);
            $table->decimal('avg_latency_ms', 10, 2)->nullable();
            $table->timestamps();
            $table->unique(['date', 'profile_id']);
        });

        Schema::create('analytics_manager_kpi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manager_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->unsignedInteger('leads_handled')->default(0);
            $table->decimal('conversion_rate', 5, 4)->default(0);
            $table->unsignedInteger('avg_response_time_sec')->nullable();
            $table->timestamps();
            $table->unique(['manager_id', 'date']);
        });

        Schema::create('analytics_faq_gaps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('question_hash', 64);
            $table->unsignedInteger('count')->default(1);
            $table->json('sample_questions')->nullable();
            $table->timestamps();
            $table->unique(['project_id', 'question_hash']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_faq_gaps');
        Schema::dropIfExists('analytics_manager_kpi');
        Schema::dropIfExists('analytics_ai_effectiveness');
        Schema::dropIfExists('analytics_hot_leads_daily');
        Schema::dropIfExists('analytics_intents');
        Schema::dropIfExists('analytics_dialog_metrics');
        Schema::dropIfExists('analytics_events');
    }
};
