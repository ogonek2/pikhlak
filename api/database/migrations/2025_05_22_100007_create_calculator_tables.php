<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calculator_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('currency', 3)->default('USD');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('calculator_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained('calculator_profiles')->cascadeOnDelete();
            $table->string('code');
            $table->text('formula');
            $table->json('variables')->nullable();
            $table->unsignedSmallInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['profile_id', 'code']);
        });

        Schema::create('calculator_coefficients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained('calculator_profiles')->cascadeOnDelete();
            $table->string('key');
            $table->decimal('value', 15, 4);
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->timestamps();
            $table->index(['profile_id', 'key']);
        });

        Schema::create('calculator_tax_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained('calculator_profiles')->cascadeOnDelete();
            $table->string('country', 2);
            $table->string('name');
            $table->json('rule');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('calculator_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained('calculator_profiles')->cascadeOnDelete();
            $table->foreignId('chat_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained()->nullOnDelete();
            $table->json('input');
            $table->json('output');
            $table->timestamps();
            $table->index(['profile_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calculator_runs');
        Schema::dropIfExists('calculator_tax_rules');
        Schema::dropIfExists('calculator_coefficients');
        Schema::dropIfExists('calculator_rules');
        Schema::dropIfExists('calculator_profiles');
    }
};
