<?php

namespace App\Services\Analytics\Collectors;

use App\Models\TrafficChannel;
use Carbon\Carbon;

/**
 * TikTok Marketing API + ограниченная органика.
 *
 * @see https://business-api.tiktok.com/portal/docs?id=1738864835814401
 */
class TikTokAnalyticsCollector extends AbstractAnalyticsCollector
{
    public function platform(): string
    {
        return 'tiktok';
    }

    public function fetch(TrafficChannel $channel, Carbon $from, Carbon $to): array
    {
        if ($this->useDemo($channel)) {
            return $this->demoPayload($channel, $from, $to);
        }

        $creds = $channel->credentials;
        $advertiserId = $creds['advertiser_id'];
        $token = $creds['access_token'];

        $response = $this->httpClient()
            ->withHeaders(['Access-Token' => $token])
            ->get('https://business-api.tiktok.com/open_api/v1.3/report/integrated/get/', [
                'advertiser_id' => $advertiserId,
                'report_type' => 'BASIC',
                'data_level' => 'AUCTION_CAMPAIGN',
                'dimensions' => json_encode(['stat_time_day', 'campaign_id']),
                'metrics' => json_encode(['spend', 'impressions', 'clicks', 'conversion']),
                'start_date' => $from->toDateString(),
                'end_date' => $to->toDateString(),
                'page_size' => 1000,
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException('TikTok API: '.$response->body());
        }

        $list = $response->json('data.list', []);

        return $this->parseReport($list);
    }

    /** @param  list<array<string, mixed>>  $list */
    private function parseReport(array $list): array
    {
        $byDate = [];
        $campaigns = [];

        foreach ($list as $item) {
            $dims = $item['dimensions'] ?? [];
            $metrics = $item['metrics'] ?? [];
            $date = substr((string) ($dims['stat_time_day'] ?? ''), 0, 10) ?: now()->toDateString();

            $byDate[$date] ??= [
                'stat_date' => $date,
                'impressions' => 0,
                'clicks' => 0,
                'leads' => 0,
                'applications' => 0,
                'views' => 0,
                'subscribers' => 0,
                'likes' => 0,
                'comments' => 0,
                'spend' => 0,
                'revenue' => null,
                'source_type' => 'paid',
                'metadata' => ['collector' => 'tiktok'],
            ];

            $impressions = (int) ($metrics['impressions'] ?? 0);
            $clicks = (int) ($metrics['clicks'] ?? 0);
            $leads = (int) ($metrics['conversion'] ?? 0);
            $spend = (float) ($metrics['spend'] ?? 0);

            $byDate[$date]['impressions'] += $impressions;
            $byDate[$date]['clicks'] += $clicks;
            $byDate[$date]['leads'] += $leads;
            $byDate[$date]['spend'] += $spend;

            $campaigns[] = [
                'external_id' => (string) ($dims['campaign_id'] ?? uniqid('tt_', true)),
                'name' => 'TikTok Campaign '.$dims['campaign_id'],
                'campaign_type' => 'paid',
                'stat_date' => $date,
                'impressions' => $impressions,
                'clicks' => $clicks,
                'leads' => $leads,
                'spend' => $spend,
                'metadata' => [],
            ];
        }

        return ['rows' => array_values($byDate), 'campaigns' => $campaigns];
    }
}
