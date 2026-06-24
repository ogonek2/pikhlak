<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rental_clients', function (Blueprint $table) {
            if (! Schema::hasColumn('rental_clients', 'crm_external_id')) {
                $table->unsignedBigInteger('crm_external_id')->nullable()->after('bot_id');
                $table->index(['project_id', 'crm_external_id']);
            }
            if (! Schema::hasColumn('rental_clients', 'link_token')) {
                $table->string('link_token', 64)->nullable()->unique()->after('crm_external_id');
            }
            if (! Schema::hasColumn('rental_clients', 'notifications_enabled')) {
                $table->boolean('notifications_enabled')->default(true)->after('status');
            }
        });

        if (! Schema::hasTable('client_notification_rules')) {
            Schema::create('client_notification_rules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->constrained()->cascadeOnDelete();
                $table->string('event_type', 40);
                $table->json('offset_days');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->unique(['project_id', 'event_type']);
            });
        }

        if (! Schema::hasTable('client_notification_logs')) {
            Schema::create('client_notification_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('rental_client_id')->constrained()->cascadeOnDelete();
                $table->string('event_type', 40);
                $table->string('notifiable_type', 80);
                $table->unsignedBigInteger('notifiable_id');
                $table->smallInteger('offset_days');
                $table->date('event_date');
                $table->string('status', 20)->default('sent');
                $table->unsignedBigInteger('telegram_message_id')->nullable();
                $table->text('error_message')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->timestamps();
                $table->unique(
                    ['rental_client_id', 'event_type', 'notifiable_type', 'notifiable_id', 'offset_days'],
                    'client_notification_logs_unique'
                );
                $table->index(['event_date', 'event_type']);
            });
        }

        if (! Schema::hasTable('rental_client_invoices')) {
            Schema::create('rental_client_invoices', function (Blueprint $table) {
                $table->id();
                $table->foreignId('rental_client_id')->constrained()->cascadeOnDelete();
                $table->foreignId('rental_client_payment_id')->nullable()->constrained()->nullOnDelete();
                $table->string('invoice_number', 40)->unique();
                $table->decimal('amount', 12, 2);
                $table->string('currency', 3)->default('UAH');
                $table->string('pdf_path', 255)->nullable();
                $table->string('qr_path', 255)->nullable();
                $table->string('payment_url', 500)->nullable();
                $table->string('status', 20)->default('issued');
                $table->timestamp('issued_at')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_client_invoices');
        Schema::dropIfExists('client_notification_logs');
        Schema::dropIfExists('client_notification_rules');

        Schema::table('rental_clients', function (Blueprint $table) {
            $columns = ['crm_external_id', 'link_token', 'notifications_enabled'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('rental_clients', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
