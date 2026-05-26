<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referral_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('code')->unique();
            $table->string('name');
            $table->json('utm_defaults')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('referral_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('referral_campaigns')->cascadeOnDelete();
            $table->string('code')->unique();
            $table->foreignId('telegram_user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('manager_id')->nullable()->constrained('managers')->nullOnDelete();
            $table->unsignedInteger('clicks_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('referral_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('link_id')->constrained('referral_links')->cascadeOnDelete();
            $table->enum('event_type', ['click', 'start', 'lead_created', 'converted']);
            $table->foreignId('lead_id')->nullable()->constrained()->nullOnDelete();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->index(['link_id', 'event_type', 'created_at']);
        });

        Schema::create('referral_attributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->foreignId('link_id')->constrained('referral_links')->cascadeOnDelete();
            $table->timestamp('first_touch_at');
            $table->timestamp('last_touch_at')->nullable();
            $table->timestamps();
            $table->unique('lead_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referral_attributions');
        Schema::dropIfExists('referral_events');
        Schema::dropIfExists('referral_links');
        Schema::dropIfExists('referral_campaigns');
    }
};
