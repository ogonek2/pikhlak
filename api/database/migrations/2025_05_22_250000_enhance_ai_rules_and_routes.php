<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE ai_prompt_rules MODIFY COLUMN type VARCHAR(32) NOT NULL DEFAULT 'instruction'");

        Schema::table('ai_prompt_rules', function (Blueprint $table) {
            $table->string('name', 120)->nullable()->after('profile_id');
        });
    }

    public function down(): void
    {
        Schema::table('ai_prompt_rules', function (Blueprint $table) {
            $table->dropColumn('name');
        });
    }
};
