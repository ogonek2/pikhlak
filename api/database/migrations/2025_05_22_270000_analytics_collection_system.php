<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('traffic_channels', function (Blueprint $table) {
            if (! Schema::hasColumn('traffic_channels', 'connection_status')) {
                $table->string('connection_status', 20)->default('disconnected')->after('api_connected');
            }
            if (! Schema::hasColumn('traffic_channels', 'credentials')) {
                $table->text('credentials')->nullable()->after('config');
            }
            if (! Schema::hasColumn('traffic_channels', 'last_synced_at')) {
                $table->timestamp('last_synced_at')->nullable()->after('credentials');
            }
            if (! Schema::hasColumn('traffic_channels', 'last_sync_error')) {
                $table->text('last_sync_error')->nullable()->after('last_synced_at');
            }
        });

        Schema::table('traffic_channel_stats', function (Blueprint $table) {
            if (! Schema::hasColumn('traffic_channel_stats', 'views')) {
                $table->unsignedInteger('views')->default(0)->after('leads');
            }
            if (! Schema::hasColumn('traffic_channel_stats', 'applications')) {
                $table->unsignedInteger('applications')->default(0)->after('views');
            }
            if (! Schema::hasColumn('traffic_channel_stats', 'subscribers')) {
                $table->unsignedInteger('subscribers')->default(0)->after('applications');
            }
            if (! Schema::hasColumn('traffic_channel_stats', 'likes')) {
                $table->unsignedInteger('likes')->default(0)->after('subscribers');
            }
            if (! Schema::hasColumn('traffic_channel_stats', 'comments')) {
                $table->unsignedInteger('comments')->default(0)->after('likes');
            }
            if (! Schema::hasColumn('traffic_channel_stats', 'source_type')) {
                $table->string('source_type', 20)->default('total')->after('comments');
            }
        });

        if (! $this->indexExists('traffic_channel_stats', 'traffic_channel_stats_daily_unique')) {
            if (! $this->indexExists('traffic_channel_stats', 'traffic_channel_stats_channel_id_index')) {
                Schema::table('traffic_channel_stats', function (Blueprint $table) {
                    $table->index('traffic_channel_id', 'traffic_channel_stats_channel_id_index');
                });
            }

            Schema::table('traffic_channel_stats', function (Blueprint $table) {
                $table->dropUnique(['traffic_channel_id', 'stat_date']);
                $table->unique(['traffic_channel_id', 'stat_date', 'source_type'], 'traffic_channel_stats_daily_unique');
            });
        }

        if (! Schema::hasTable('traffic_sync_logs')) {
            Schema::create('traffic_sync_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('traffic_channel_id')->constrained()->cascadeOnDelete();
                $table->string('status', 20);
                $table->unsignedSmallInteger('days_synced')->default(0);
                $table->unsignedInteger('rows_upserted')->default(0);
                $table->text('message')->nullable();
                $table->json('details')->nullable();
                $table->timestamp('started_at');
                $table->timestamp('finished_at')->nullable();
                $table->timestamps();
                $table->index(['traffic_channel_id', 'created_at']);
            });
        }

        if (! Schema::hasTable('traffic_campaign_stats')) {
            Schema::create('traffic_campaign_stats', function (Blueprint $table) {
                $table->id();
                $table->foreignId('traffic_channel_id')->constrained()->cascadeOnDelete();
                $table->string('external_id', 120);
                $table->string('name', 255);
                $table->string('campaign_type', 40)->default('paid');
                $table->date('stat_date');
                $table->unsignedInteger('impressions')->default(0);
                $table->unsignedInteger('clicks')->default(0);
                $table->unsignedInteger('leads')->default(0);
                $table->decimal('spend', 12, 2)->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->unique(['traffic_channel_id', 'external_id', 'stat_date'], 'traffic_campaign_stats_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('traffic_campaign_stats');
        Schema::dropIfExists('traffic_sync_logs');

        if ($this->indexExists('traffic_channel_stats', 'traffic_channel_stats_daily_unique')) {
            Schema::table('traffic_channel_stats', function (Blueprint $table) {
                $table->dropUnique('traffic_channel_stats_daily_unique');
                $table->unique(['traffic_channel_id', 'stat_date']);
            });
        }

        if ($this->indexExists('traffic_channel_stats', 'traffic_channel_stats_channel_id_index')) {
            Schema::table('traffic_channel_stats', function (Blueprint $table) {
                $table->dropIndex('traffic_channel_stats_channel_id_index');
            });
        }

        Schema::table('traffic_channel_stats', function (Blueprint $table) {
            $columns = ['views', 'applications', 'subscribers', 'likes', 'comments', 'source_type'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('traffic_channel_stats', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('traffic_channels', function (Blueprint $table) {
            $columns = ['connection_status', 'credentials', 'last_synced_at', 'last_sync_error'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('traffic_channels', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $connection = Schema::getConnection()->getDriverName();

        if ($connection !== 'mysql') {
            return false;
        }

        $database = Schema::getConnection()->getDatabaseName();

        $result = DB::selectOne(
            'SELECT COUNT(*) AS cnt FROM information_schema.statistics
             WHERE table_schema = ? AND table_name = ? AND index_name = ?',
            [$database, $table, $indexName]
        );

        return (int) ($result->cnt ?? 0) > 0;
    }
};
