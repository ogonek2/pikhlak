<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_routes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->json('intent_keywords')->nullable();
            $table->foreignId('model_id')->nullable()->constrained('ai_models')->nullOnDelete();
            $table->foreignId('profile_id')->nullable()->constrained('ai_profiles')->nullOnDelete();
            $table->json('pipeline')->nullable();
            $table->json('data_sources')->nullable();
            $table->text('extra_instruction')->nullable();
            $table->unsignedSmallInteger('priority')->default(50);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['project_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_routes');
    }
};
