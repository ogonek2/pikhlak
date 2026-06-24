<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bots', function (Blueprint $table) {
            $table->string('type', 20)->default('warming')->after('name');
        });

        DB::table('bots')->update(['type' => 'warming']);

        Schema::table('bots', function (Blueprint $table) {
            $table->unique(['project_id', 'type']);
            $table->index(['project_id', 'type', 'is_active']);
        });

        Schema::create('rental_clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bot_id')->nullable()->constrained('bots')->nullOnDelete();
            $table->uuid('uuid')->unique();
            $table->string('full_name');
            $table->string('email')->nullable();
            $table->unsignedBigInteger('telegram_user_id')->nullable();
            $table->unsignedBigInteger('telegram_chat_id')->nullable();
            $table->string('status', 30)->default('active');
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['project_id', 'status']);
            $table->index(['telegram_chat_id']);
        });

        Schema::create('rental_client_phones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_client_id')->constrained()->cascadeOnDelete();
            $table->string('label', 40)->default('mobile');
            $table->string('phone', 40);
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
        });

        Schema::create('rental_client_vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_client_id')->constrained()->cascadeOnDelete();
            $table->string('make', 80);
            $table->string('model', 80);
            $table->unsignedSmallInteger('year')->nullable();
            $table->string('color', 40)->nullable();
            $table->string('plate_number', 20)->nullable();
            $table->string('vin', 40)->nullable();
            $table->unsignedInteger('mileage')->nullable();
            $table->boolean('is_current')->default(true);
            $table->timestamps();
        });

        Schema::create('rental_client_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('rental_client_vehicle_id')->nullable()->constrained()->nullOnDelete();
            $table->string('contract_number', 60)->nullable();
            $table->date('rent_start');
            $table->date('rent_end')->nullable();
            $table->decimal('monthly_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->nullable();
            $table->string('currency', 3)->default('USD');
            $table->boolean('buyout_option')->default(true);
            $table->string('status', 30)->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('rental_client_insurances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_client_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 120);
            $table->string('policy_number', 80)->nullable();
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            $table->decimal('premium_amount', 12, 2)->nullable();
            $table->text('coverage_notes')->nullable();
            $table->timestamps();
        });

        Schema::create('rental_client_maintenances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('rental_client_vehicle_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 30)->default('service');
            $table->string('title', 160);
            $table->date('scheduled_at')->nullable();
            $table->date('completed_at')->nullable();
            $table->unsignedInteger('mileage_at')->nullable();
            $table->decimal('cost', 12, 2)->nullable();
            $table->string('status', 30)->default('planned');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('rental_client_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_client_id')->constrained()->cascadeOnDelete();
            $table->string('type', 30)->default('rent');
            $table->decimal('amount', 12, 2);
            $table->date('due_date');
            $table->date('paid_at')->nullable();
            $table->string('status', 30)->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['rental_client_id', 'status', 'due_date']);
        });

        Schema::create('traffic_channels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('slug', 40);
            $table->string('name', 80);
            $table->boolean('is_active')->default(true);
            $table->boolean('api_connected')->default(false);
            $table->json('config')->nullable();
            $table->timestamps();
            $table->unique(['project_id', 'slug']);
        });

        Schema::create('traffic_channel_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('traffic_channel_id')->constrained()->cascadeOnDelete();
            $table->date('stat_date');
            $table->unsignedInteger('impressions')->default(0);
            $table->unsignedInteger('clicks')->default(0);
            $table->unsignedInteger('leads')->default(0);
            $table->decimal('spend', 12, 2)->nullable();
            $table->decimal('revenue', 12, 2)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['traffic_channel_id', 'stat_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('traffic_channel_stats');
        Schema::dropIfExists('traffic_channels');
        Schema::dropIfExists('rental_client_payments');
        Schema::dropIfExists('rental_client_maintenances');
        Schema::dropIfExists('rental_client_insurances');
        Schema::dropIfExists('rental_client_contracts');
        Schema::dropIfExists('rental_client_vehicles');
        Schema::dropIfExists('rental_client_phones');
        Schema::dropIfExists('rental_clients');

        Schema::table('bots', function (Blueprint $table) {
            $table->dropUnique(['project_id', 'type']);
            $table->dropIndex(['project_id', 'type', 'is_active']);
            $table->dropColumn('type');
        });
    }
};
