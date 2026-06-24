<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rental_clients', function (Blueprint $table) {
            if (! Schema::hasColumn('rental_clients', 'crm_synced_at')) {
                $table->timestamp('crm_synced_at')->nullable()->after('crm_external_id');
            }
        });

        foreach ([
            'rental_client_contracts' => 'rental_client_id',
            'rental_client_payments' => 'rental_client_id',
            'rental_client_maintenances' => 'rental_client_id',
            'rental_client_insurances' => 'rental_client_id',
        ] as $tableName => $after) {
            if (! Schema::hasColumn($tableName, 'crm_external_id')) {
                Schema::table($tableName, function (Blueprint $table) use ($after) {
                    $table->string('crm_external_id', 80)->nullable()->after($after);
                    $table->index(['crm_external_id']);
                });
            }
        }

        if (! Schema::hasTable('crm_sync_logs')) {
            Schema::create('crm_sync_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->constrained()->cascadeOnDelete();
                $table->string('status', 20);
                $table->unsignedInteger('clients_synced')->default(0);
                $table->unsignedInteger('clients_failed')->default(0);
                $table->text('message')->nullable();
                $table->json('details')->nullable();
                $table->timestamp('started_at');
                $table->timestamp('finished_at')->nullable();
                $table->timestamps();
                $table->index(['project_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_sync_logs');

        Schema::table('rental_clients', function (Blueprint $table) {
            if (Schema::hasColumn('rental_clients', 'crm_synced_at')) {
                $table->dropColumn('crm_synced_at');
            }
        });

        foreach (['rental_client_contracts', 'rental_client_payments', 'rental_client_maintenances', 'rental_client_insurances'] as $tableName) {
            if (Schema::hasColumn($tableName, 'crm_external_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropColumn('crm_external_id');
                });
            }
        }
    }
};
