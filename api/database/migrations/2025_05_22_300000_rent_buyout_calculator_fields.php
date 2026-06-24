<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rental_client_vehicles', function (Blueprint $table) {
            if (! Schema::hasColumn('rental_client_vehicles', 'car_id')) {
                $table->foreignId('car_id')->nullable()->after('rental_client_id')->constrained('cars')->nullOnDelete();
            }
        });

        Schema::table('rental_client_contracts', function (Blueprint $table) {
            if (! Schema::hasColumn('rental_client_contracts', 'car_id')) {
                $table->foreignId('car_id')->nullable()->after('rental_client_vehicle_id')->constrained('cars')->nullOnDelete();
            }
            if (! Schema::hasColumn('rental_client_contracts', 'first_payment')) {
                $table->decimal('first_payment', 12, 2)->default(0)->after('monthly_amount');
            }
            if (! Schema::hasColumn('rental_client_contracts', 'term_years')) {
                $table->unsignedSmallInteger('term_years')->nullable()->after('first_payment');
            }
            if (! Schema::hasColumn('rental_client_contracts', 'overpayment_rate')) {
                $table->decimal('overpayment_rate', 6, 4)->nullable()->after('term_years');
            }
            if (! Schema::hasColumn('rental_client_contracts', 'weekly_amount')) {
                $table->decimal('weekly_amount', 12, 2)->nullable()->after('overpayment_rate');
            }
            if (! Schema::hasColumn('rental_client_contracts', 'period_weeks')) {
                $table->unsignedSmallInteger('period_weeks')->default(4)->after('weekly_amount');
            }
            if (! Schema::hasColumn('rental_client_contracts', 'calculation_snapshot')) {
                $table->json('calculation_snapshot')->nullable()->after('notes');
            }
        });

        Schema::table('rental_client_payments', function (Blueprint $table) {
            if (! Schema::hasColumn('rental_client_payments', 'week_number')) {
                $table->unsignedSmallInteger('week_number')->nullable()->after('type');
            }
            if (! Schema::hasColumn('rental_client_payments', 'period_index')) {
                $table->unsignedSmallInteger('period_index')->nullable()->after('week_number');
            }
        });
    }

    public function down(): void
    {
        Schema::table('rental_client_payments', function (Blueprint $table) {
            $table->dropColumn(['week_number', 'period_index']);
        });

        Schema::table('rental_client_contracts', function (Blueprint $table) {
            $table->dropForeign(['car_id']);
            $table->dropColumn([
                'car_id', 'first_payment', 'term_years', 'overpayment_rate',
                'weekly_amount', 'period_weeks', 'calculation_snapshot',
            ]);
        });

        Schema::table('rental_client_vehicles', function (Blueprint $table) {
            $table->dropForeign(['car_id']);
            $table->dropColumn('car_id');
        });
    }
};
