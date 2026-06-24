<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Models\TrafficChannel;
use App\Services\Analytics\TrafficSyncService;
use Illuminate\Console\Command;

class SyncTrafficAnalytics extends Command
{
    protected $signature = 'pikhlak:sync-traffic-analytics
                            {--project= : Project slug}
                            {--channel= : Channel slug (meta, tiktok, youtube, olx, instagram)}
                            {--days= : Days to sync}';

    protected $description = 'Sync traffic analytics from Meta, TikTok, YouTube, OLX';

    public function handle(TrafficSyncService $sync): int
    {
        $projectSlug = $this->option('project') ?? config('pikhlak.default_project_slug', 'pikhlak');
        $project = Project::query()->where('slug', $projectSlug)->first();
        if (! $project) {
            $this->error("Project not found: {$projectSlug}");

            return self::FAILURE;
        }

        $days = $this->option('days') ? (int) $this->option('days') : null;
        $channelSlug = $this->option('channel');

        if ($channelSlug) {
            $channel = TrafficChannel::query()
                ->where('project_id', $project->id)
                ->where('slug', $channelSlug)
                ->first();
            if (! $channel) {
                $this->error("Channel not found: {$channelSlug}");

                return self::FAILURE;
            }
            $log = $sync->sync($channel, $days);
            $this->info("{$channel->name}: {$log->status} — {$log->message}");

            return $log->status === 'success' ? self::SUCCESS : self::FAILURE;
        }

        $count = $sync->syncProject($project->id, $days);
        $this->info("Synced {$count} channel(s) for project {$project->name}");

        return self::SUCCESS;
    }
}
