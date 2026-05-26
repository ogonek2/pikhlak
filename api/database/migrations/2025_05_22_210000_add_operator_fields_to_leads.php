<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->timestamp('operator_requested_at')->nullable()->after('last_contacted_at');
            $table->timestamp('operator_handled_at')->nullable()->after('operator_requested_at');
            $table->index(['project_id', 'operator_requested_at']);
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropIndex(['project_id', 'operator_requested_at']);
            $table->dropColumn(['operator_requested_at', 'operator_handled_at']);
        });
    }
};
