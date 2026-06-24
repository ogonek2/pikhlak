<?php

namespace App\Services\Analytics\Collectors;

use App\Models\TrafficChannel;
use Carbon\Carbon;

/**
 * Meta Marketing API + Graph API.
 *
 * @see https://developers.facebook.com/docs/marketing-api/insights
 * @see https://developers.facebook.com/docs/graph-api/reference/insights
 */
class MetaAnalyticsCollector extends AbstractAnalyticsCollector
{
    public function platform(): string
    {
        return 'meta';
    }

    public function fetch(TrafficChannel $channel, Carbon $from, Carbon $to): array
    {
        if ($this->useDemo($channel)) {
            return $this->demoPayload($channel, $from, $to);
        }

        $creds = $channel->credentials;
        $token = $creds['access_token'];
        $adAccountId = $creds['ad_account_id'];
        $account = str_starts_with($adAccountId, 'act_') ? $adAccountId : "act_{$adAccountId}";

        $response = $this->httpClient()->get("https://graph.facebook.com/v21.0/{$account}/insights", [
            'access_token' => $token,
            'time_range' => json_encode(['since' => $from->toDateString(), 'until' => $to->toDateString()]),
            'time_increment' => 1,
            'fields' => 'impressions,clicks,spend,actions,campaign_name,campaign_id',
            'level' => 'campaign',
        ]);

        if (! $response->successful()) {
            throw new \RuntimeException('Meta API: '.$response->body());
        }

        return $this->parseInsights($response->json('data', []), $from, $to);
    }

    /** @param  list<array<string, mixed>>  $data */
    private function parseInsights(array $data, Carbon $from, Carbon $to): array
    {
        $byDate = [];
        $campaigns = [];

        foreach ($data as $row) {
            $date = $row['date_start'] ?? $to->toDateString();
            $clicks = (int) ($row['clicks'] ?? 0);
            $impressions = (int) ($row['impressions'] ?? 0);
            $spend = (float) ($row['spend'] ?? 0);
            $leads = $this->extractActionCount($row['actions'] ?? [], ['lead', 'onsite_conversion.lead_grouped']);

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
                'metadata' => ['collector' => 'meta'],
            ];

            $byDate[$date]['impressions'] += $impressions;
            $byDate[$date]['clicks'] += $clicks;
            $byDate[$date]['leads'] += $leads;
            $byDate[$date]['spend'] += $spend;

            $campaigns[] = [
                'external_id' => (string) ($row['campaign_id'] ?? uniqid('meta_', true)),
                'name' => (string) ($row['campaign_name'] ?? 'Campaign'),
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

    /** @param  list<array<string, mixed>>  $actions */
    private function extractActionCount(array $actions, array $types): int
    {
        $sum = 0;
        foreach ($actions as $action) {
            if (in_array($action['action_type'] ?? '', $types, true)) {
                $sum += (int) ($action['value'] ?? 0);
            }
        }

        return $sum;
    }
}
