<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_models', function (Blueprint $table) {
            $table->id();
            $table->string('provider')->default('openai');
            $table->string('model_name');
            $table->json('config')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('ai_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bot_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->json('personality')->nullable();
            $table->decimal('temperature', 3, 2)->default(0.70);
            $table->unsignedInteger('max_tokens')->default(1024);
            $table->foreignId('model_id')->nullable()->constrained('ai_models')->nullOnDelete();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['project_id', 'is_default']);
        });

        Schema::create('ai_system_prompts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained('ai_profiles')->cascadeOnDelete();
            $table->unsignedInteger('version')->default(1);
            $table->longText('content');
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['profile_id', 'version']);
        });

        Schema::create('ai_prompt_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained('ai_profiles')->cascadeOnDelete();
            $table->enum('type', ['instruction', 'constraint', 'example', 'tool'])->default('instruction');
            $table->unsignedSmallInteger('priority')->default(0);
            $table->json('condition')->nullable();
            $table->text('instruction');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('ai_allowed_topics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained('ai_profiles')->cascadeOnDelete();
            $table->string('topic');
            $table->json('keywords')->nullable();
            $table->timestamps();
        });

        Schema::create('ai_forbidden_topics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained('ai_profiles')->cascadeOnDelete();
            $table->string('topic');
            $table->json('keywords')->nullable();
            $table->enum('action', ['block', 'fallback', 'escalate'])->default('fallback');
            $table->timestamps();
        });

        Schema::create('ai_response_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained('ai_profiles')->cascadeOnDelete();
            $table->string('code');
            $table->text('template');
            $table->string('locale', 10)->default('uk');
            $table->timestamps();
            $table->unique(['profile_id', 'code', 'locale']);
        });

        Schema::create('ai_warming_scenarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained('ai_profiles')->cascadeOnDelete();
            $table->string('name');
            $table->json('steps');
            $table->json('triggers')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('ai_context_memories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_id')->constrained()->cascadeOnDelete();
            $table->longText('summary')->nullable();
            $table->unsignedInteger('tokens_estimate')->default(0);
            $table->timestamps();
            $table->unique('chat_id');
        });

        Schema::create('ai_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('profile_id')->constrained('ai_profiles')->cascadeOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();
        });

        Schema::create('ai_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('ai_conversations')->cascadeOnDelete();
            $table->enum('role', ['system', 'user', 'assistant', 'tool']);
            $table->longText('content');
            $table->unsignedInteger('tokens_in')->default(0);
            $table->unsignedInteger('tokens_out')->default(0);
            $table->unsignedInteger('latency_ms')->nullable();
            $table->timestamps();
            $table->index(['conversation_id', 'created_at']);
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->foreign('ai_message_id')->references('id')->on('ai_messages')->nullOnDelete();
        });

        Schema::create('ai_faq_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('question');
            $table->text('answer');
            $table->string('locale', 10)->default('uk');
            $table->json('tags')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['project_id', 'is_active']);
        });

        Schema::create('ai_faq_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('faq_id')->constrained('ai_faq_items')->cascadeOnDelete();
            $table->foreignId('chat_id')->constrained()->cascadeOnDelete();
            $table->decimal('score', 5, 4)->nullable();
            $table->timestamps();
        });

        Schema::create('ai_fallback_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained('ai_profiles')->cascadeOnDelete();
            $table->string('trigger');
            $table->enum('fallback_type', ['template', 'faq', 'human', 'retry'])->default('template');
            $table->foreignId('template_id')->nullable()->constrained('ai_response_templates')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('ai_request_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('model_id')->nullable()->constrained('ai_models')->nullOnDelete();
            $table->string('prompt_hash', 64)->nullable();
            $table->enum('status', ['success', 'failed', 'timeout', 'blocked'])->default('success');
            $table->text('error')->nullable();
            $table->decimal('cost_usd', 10, 6)->nullable();
            $table->unsignedInteger('latency_ms')->nullable();
            $table->timestamps();
            $table->index(['chat_id', 'created_at']);
        });

        Schema::create('ai_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_message_id')->constrained('ai_messages')->cascadeOnDelete();
            $table->string('dimension');
            $table->decimal('score', 5, 2);
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('ai_training_samples', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->text('input');
            $table->text('expected_output');
            $table->json('tags')->nullable();
            $table->boolean('is_approved')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropForeign(['ai_message_id']);
        });
        Schema::dropIfExists('ai_training_samples');
        Schema::dropIfExists('ai_scores');
        Schema::dropIfExists('ai_request_logs');
        Schema::dropIfExists('ai_fallback_rules');
        Schema::dropIfExists('ai_faq_matches');
        Schema::dropIfExists('ai_faq_items');
        Schema::dropIfExists('ai_messages');
        Schema::dropIfExists('ai_conversations');
        Schema::dropIfExists('ai_context_memories');
        Schema::dropIfExists('ai_warming_scenarios');
        Schema::dropIfExists('ai_response_templates');
        Schema::dropIfExists('ai_forbidden_topics');
        Schema::dropIfExists('ai_allowed_topics');
        Schema::dropIfExists('ai_prompt_rules');
        Schema::dropIfExists('ai_system_prompts');
        Schema::dropIfExists('ai_profiles');
        Schema::dropIfExists('ai_models');
    }
};
