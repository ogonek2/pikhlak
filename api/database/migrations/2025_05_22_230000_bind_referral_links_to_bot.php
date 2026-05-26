<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('referral_links', function (Blueprint $table) {
            $table->foreignId('bot_id')->nullable()->after('project_id')->constrained('bots')->cascadeOnDelete();
        });

        $links = DB::table('referral_links')->whereNull('bot_id')->get(['id', 'project_id']);
        foreach ($links as $row) {
            $botId = DB::table('bots')->where('project_id', $row->project_id)->value('id');
            if ($botId) {
                DB::table('referral_links')->where('id', $row->id)->update(['bot_id' => $botId]);
            }
        }

        Schema::table('referral_links', function (Blueprint $table) {
            $table->dropUnique(['code']);
        });

        Schema::table('referral_links', function (Blueprint $table) {
            $table->unique(['bot_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::table('referral_links', function (Blueprint $table) {
            $table->dropUnique(['bot_id', 'code']);
            $table->unique('code');
            $table->dropConstrainedForeignId('bot_id');
        });
    }
};
