<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_manager_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('rental_client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bot_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('telegram_user_id')->nullable();
            $table->unsignedBigInteger('telegram_chat_id')->nullable();
            $table->string('source', 32)->default('button');
            $table->text('client_message')->nullable();
            $table->enum('status', ['pending', 'in_progress', 'resolved', 'cancelled'])->default('pending');
            $table->text('admin_notes')->nullable();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('handled_at')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'status', 'created_at']);
            $table->index(['rental_client_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_manager_requests');
    }
};
