<?php

namespace App\Services\Analytics\Collectors;

use App\Models\TrafficChannel;
use App\Services\Analytics\Contracts\AnalyticsCollectorInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

abstract class AbstractAnalyticsCollector implements AnalyticsCollectorInterface
{
    public function isConfigured(TrafficChannel $channel): bool
    {
        $creds = $channel->credentials ?? [];
        $fields = config("analytics.platforms.{$channel->slug}.credential_fields", []);

        foreach ($fields as $field) {
            if (($field['required'] ?? false) && empty($creds[$field['key'] ?? ''] ?? null)) {
                return false;
            }
        }

        return $fields !== [] && $creds !== [];
    }

    protected function useDemo(TrafficChannel $channel): bool
    {
        return config('analytics.demo_when_unconfigured', true) && ! $this->isConfigured($channel);
    }

    /** @return array{rows: list<array<string, mixed>>, campaigns: list<array<string, mixed>>} */
    protected function demoPayload(TrafficChannel $channel, Carbon $from, Carbon $to): array
    {
        $preset = collect(config('client_portal.demo_channels', []))->firstWhere('slug', $channel->slug) ?? [];
        $rows = [];
        $cursor = $from->copy();

        while ($cursor->lte($to)) {
            $clicks = random_int($preset['clicks_min'] ?? 15, $preset['clicks_max'] ?? 60);
            $leads = random_int($preset['leads_min'] ?? 1, $preset['leads_max'] ?? 5);
            $rows[] = [
                'stat_date' => $cursor->toDateString(),
                'impressions' => $clicks * random_int(8, 14),
                'clicks' => $clicks,
                'leads' => $leads,
                'applications' => (int) floor($leads * 0.6),
                'views' => random_int(200, 2000),
                'subscribers' => random_int(0, 3),
                'likes' => random_int(5, 80),
                'comments' => random_int(0, 15),
                'spend' => round($clicks * ($preset['cpc'] ?? 0.35), 2),
                'revenue' => $leads * ($preset['revenue_per_lead'] ?? 100),
                'source_type' => 'total',
                'metadata' => ['demo' => true, 'collector' => $this->platform()],
            ];
            $cursor->addDay();
        }

        return [
            'rows' => $rows,
            'campaigns' => [
                [
                    'external_id' => 'demo-campaign-1',
                    'name' => 'Demo Campaign',
                    'campaign_type' => 'paid',
                    'stat_date' => $to->toDateString(),
                    'impressions' => array_sum(array_column($rows, 'impressions')),
                    'clicks' => array_sum(array_column($rows, 'clicks')),
                    'leads' => array_sum(array_column($rows, 'leads')),
                    'spend' => array_sum(array_column($rows, 'spend')),
                    'metadata' => ['demo' => true],
                ],
            ],
        ];
    }

    protected function httpClient(): \Illuminate\Http\Client\PendingRequest
    {
        $http = Http::timeout(60);
        if (config('ai.verify_ssl') === false) {
            $http = $http->withOptions(['verify' => false]);
        }

        return $http;
    }
}
