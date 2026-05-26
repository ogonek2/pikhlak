<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bot_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('telegram_id');
            $table->string('username')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('language_code', 10)->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->unique(['bot_id', 'telegram_id']);
        });

        Schema::create('chats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bot_id')->constrained()->cascadeOnDelete();
            $table->foreignId('telegram_user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('telegram_chat_id');
            $table->enum('type', ['private', 'group', 'supergroup', 'channel'])->default('private');
            $table->json('state')->nullable();
            $table->unsignedInteger('state_version')->default(0);
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamps();
            $table->unique(['bot_id', 'telegram_chat_id']);
            $table->index(['bot_id', 'last_activity_at']);
        });

        Schema::create('chat_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_id')->constrained()->cascadeOnDelete();
            $table->string('session_key');
            $table->json('context')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->unique(['chat_id', 'session_key']);
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_id')->constrained()->cascadeOnDelete();
            $table->enum('direction', ['inbound', 'outbound']);
            $table->unsignedBigInteger('telegram_message_id')->nullable();
            $table->enum('type', ['text', 'photo', 'document', 'video', 'audio', 'voice', 'sticker', 'location', 'contact', 'callback', 'command', 'other'])->default('text');
            $table->text('body')->nullable();
            $table->json('payload')->nullable();
            $table->foreignId('ai_message_id')->nullable();
            $table->timestamps();
            $table->index(['chat_id', 'created_at']);
            $table->index(['chat_id', 'direction']);
        });

        Schema::create('message_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained()->cascadeOnDelete();
            $table->string('telegram_file_id')->nullable();
            $table->string('mime_type')->nullable();
            $table->string('storage_path')->nullable();
            $table->unsignedInteger('file_size')->nullable();
            $table->timestamps();
        });

        Schema::create('callback_queries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('chat_id')->constrained()->cascadeOnDelete();
            $table->string('callback_id');
            $table->text('data');
            $table->timestamp('handled_at')->nullable();
            $table->timestamps();
            $table->index(['chat_id', 'created_at']);
        });

        Schema::create('commands_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_id')->constrained()->cascadeOnDelete();
            $table->string('command');
            $table->json('args')->nullable();
            $table->timestamps();
            $table->index(['chat_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commands_log');
        Schema::dropIfExists('callback_queries');
        Schema::dropIfExists('message_attachments');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('chat_sessions');
        Schema::dropIfExists('chats');
        Schema::dropIfExists('telegram_users');
    }
};
