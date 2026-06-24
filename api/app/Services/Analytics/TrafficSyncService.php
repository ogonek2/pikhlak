<?php

namespace App\Services\Analytics;

use App\Models\TrafficCampaignStat;
use App\Models\TrafficChannel;
use App\Models\TrafficChannelStat;
use App\Models\TrafficSyncLog;
use Carbon\Carbon;

class TrafficSyncService
{
    public function __construct(private readonly AnalyticsCollectorFactory $factory) {}

    public function sync(TrafficChannel $channel, ?int $days = null): TrafficSyncLog
    {
        $days ??= (int) config('analytics.sync_default_days', 30);
        $from = now()->subDays($days - 1)->startOfDay();
        $to = now()->endOfDay();

        $log = TrafficSyncLog::query()->create([
            'traffic_channel_id' => $channel->id,
            'status' => 'running',
            'days_synced' => $days,
            'started_at' => now(),
        ]);

        try {
            $collector = $this->factory->forChannel($channel);
            $payload = $collector->fetch($channel, $from, $to);
            $rowsUpserted = $this->persistDailyStats($channel, $payload['rows'] ?? []);
            $this->persistCampaignStats($channel, $payload['campaigns'] ?? []);

            $isDemo = ($payload['rows'][0]['metadata']['demo'] ?? false) === true;
            $configured = $collector->isConfigured($channel);

            $channel->update([
                'api_connected' => $configured && ! $isDemo,
                'connection_status' => $configured ? ($isDemo ? 'configured' : 'connected') : ($isDemo ? 'disconnected' : 'configured'),
                'last_synced_at' => now(),
                'last_sync_error' => null,
            ]);

            $log->update([
                'status' => 'success',
                'rows_upserted' => $rowsUpserted,
                'message' => $isDemo ? 'Демо-данные (ключи API не заданы)' : 'Синхронизация завершена',
                'details' => ['demo' => $isDemo, 'platform' => $collector->platform()],
                'finished_at' => now(),
            ]);
        } catch (\Throwable $e) {
            $channel->update([
                'connection_status' => 'error',
                'last_sync_error' => mb_substr($e->getMessage(), 0, 1000),
            ]);

            $log->update([
                'status' => 'failed',
                'message' => $e->getMessage(),
                'finished_at' => now(),
            ]);
        }

        return $log->fresh();
    }

    public function syncProject(int $projectId, ?int $days = null): int
    {
        $count = 0;
        $channels = TrafficChannel::query()->where('project_id', $projectId)->where('is_active', true)->get();
        foreach ($channels as $channel) {
            $this->sync($channel, $days);
            $count++;
        }

        return $count;
    }

    /** @param  list<array<string, mixed>>  $rows */
    private function persistDailyStats(TrafficChannel $channel, array $rows): int
    {
        $upserted = 0;
        foreach ($rows as $row) {
            TrafficChannelStat::query()->updateOrCreate(
                [
                    'traffic_channel_id' => $channel->id,
                    'stat_date' => $row['stat_date'],
                ],
                [
                    'impressions' => (int) ($row['impressions'] ?? 0),
                    'clicks' => (int) ($row['clicks'] ?? 0),
                    'leads' => (int) ($row['leads'] ?? 0),
                    'views' => (int) ($row['views'] ?? 0),
                    'applications' => (int) ($row['applications'] ?? 0),
                    'subscribers' => (int) ($row['subscribers'] ?? 0),
                    'likes' => (int) ($row['likes'] ?? 0),
                    'comments' => (int) ($row['comments'] ?? 0),
                    'spend' => $row['spend'] ?? null,
                    'revenue' => $row['revenue'] ?? null,
                    'metadata' => $row['metadata'] ?? null,
                ]
            );
            $upserted++;
        }

        return $upserted;
    }

    /** @param  list<array<string, mixed>>  $campaigns */
    private function persistCampaignStats(TrafficChannel $channel, array $campaigns): void
    {
        foreach ($campaigns as $c) {
            TrafficCampaignStat::query()->updateOrCreate(
                [
                    'traffic_channel_id' => $channel->id,
                    'external_id' => (string) $c['external_id'],
                    'stat_date' => Carbon::parse($c['stat_date'])->toDateString(),
                ],
                [
                    'name' => (string) ($c['name'] ?? 'Campaign'),
                    'campaign_type' => (string) ($c['campaign_type'] ?? 'paid'),
                    'impressions' => (int) ($c['impressions'] ?? 0),
                    'clicks' => (int) ($c['clicks'] ?? 0),
                    'leads' => (int) ($c['leads'] ?? 0),
                    'spend' => $c['spend'] ?? null,
                    'metadata' => $c['metadata'] ?? null,
                ]
            );
        }
    }
}
