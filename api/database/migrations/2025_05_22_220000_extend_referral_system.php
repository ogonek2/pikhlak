<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('referral_campaigns', function (Blueprint $table) {
            $table->text('description')->nullable()->after('name');
        });

        Schema::table('referral_links', function (Blueprint $table) {
            $table->dropForeign(['campaign_id']);
        });

        Schema::table('referral_links', function (Blueprint $table) {
            $table->unsignedBigInteger('campaign_id')->nullable()->change();
            $table->foreign('campaign_id')->references('id')->on('referral_campaigns')->nullOnDelete();
        });

        Schema::table('referral_links', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->string('name')->nullable()->after('code');
            $table->string('type', 20)->default('traffic')->after('name');
            $table->string('channel', 50)->nullable()->after('type');
            $table->foreignId('car_id')->nullable()->after('channel')->constrained('cars')->nullOnDelete();
            $table->string('partner_name')->nullable()->after('car_id');
            $table->string('partner_contact')->nullable()->after('partner_name');
            $table->decimal('partner_commission_percent', 5, 2)->nullable()->after('partner_contact');
            $table->string('utm_source')->nullable()->after('partner_commission_percent');
            $table->string('utm_medium')->nullable()->after('utm_source');
            $table->string('utm_campaign')->nullable()->after('utm_medium');
            $table->string('utm_content')->nullable()->after('utm_campaign');
            $table->string('utm_term')->nullable()->after('utm_content');
            $table->json('settings')->nullable()->after('utm_term');
            $table->text('description')->nullable()->after('settings');
            $table->text('landing_message')->nullable()->after('description');
            $table->timestamp('expires_at')->nullable()->after('landing_message');
            $table->unsignedInteger('max_starts')->nullable()->after('expires_at');
            $table->unsignedInteger('starts_count')->default(0)->after('clicks_count');
            $table->unsignedInteger('leads_count')->default(0)->after('starts_count');
            $table->unsignedInteger('conversions_count')->default(0)->after('leads_count');
            $table->index(['project_id', 'type', 'is_active']);
            $table->index(['project_id', 'channel']);
        });

        foreach (DB::table('referral_links')->whereNull('project_id')->get(['id', 'campaign_id']) as $row) {
            if (! $row->campaign_id) {
                continue;
            }
            $projectId = DB::table('referral_campaigns')->where('id', $row->campaign_id)->value('project_id');
            if ($projectId) {
                DB::table('referral_links')->where('id', $row->id)->update(['project_id' => $projectId]);
            }
        }

        Schema::table('leads', function (Blueprint $table) {
            $table->foreignId('referral_link_id')->nullable()->after('source')->constrained('referral_links')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropConstrainedForeignId('referral_link_id');
        });

        Schema::table('referral_links', function (Blueprint $table) {
            $table->dropIndex(['project_id', 'type', 'is_active']);
            $table->dropIndex(['project_id', 'channel']);
            $table->dropConstrainedForeignId('car_id');
            $table->dropConstrainedForeignId('project_id');
            $table->dropColumn([
                'name', 'type', 'channel', 'partner_name', 'partner_contact', 'partner_commission_percent',
                'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term',
                'settings', 'description', 'landing_message', 'expires_at', 'max_starts',
                'starts_count', 'leads_count', 'conversions_count',
            ]);
        });

        Schema::table('referral_campaigns', function (Blueprint $table) {
            $table->dropColumn('description');
        });
    }
};
