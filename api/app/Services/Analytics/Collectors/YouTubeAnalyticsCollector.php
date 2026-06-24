<?php

namespace App\Services\Analytics\Collectors;

use App\Models\TrafficChannel;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

/**
 * YouTube Analytics API.
 *
 * @see https://developers.google.com/youtube/analytics/reference/reports/query
 */
class YouTubeAnalyticsCollector extends AbstractAnalyticsCollector
{
    public function platform(): string
    {
        return 'youtube';
    }

    public function fetch(TrafficChannel $channel, Carbon $from, Carbon $to): array
    {
        if ($this->useDemo($channel)) {
            return $this->demoPayload($channel, $from, $to);
        }

        $creds = $channel->credentials;
        $accessToken = $this->accessToken($creds);
        $channelId = $creds['channel_id'];

        $response = $this->httpClient()
            ->withToken($accessToken)
            ->get('https://youtubeanalytics.googleapis.com/v2/reports', [
                'ids' => "channel=={$channelId}",
                'startDate' => $from->toDateString(),
                'endDate' => $to->toDateString(),
                'metrics' => 'views,subscribersGained,likes,comments,estimatedMinutesWatched,averageViewDuration',
                'dimensions' => 'day',
                'sort' => 'day',
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException('YouTube Analytics API: '.$response->body());
        }

        return $this->parseAnalytics($response->json());
    }

    /** @param  array<string, mixed>  $creds */
    private function accessToken(array $creds): string
    {
        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'client_id' => $creds['client_id'],
            'client_secret' => $creds['client_secret'],
            'refresh_token' => $creds['refresh_token'],
            'grant_type' => 'refresh_token',
        ]);

        if (! $response->successful()) {
            throw new \RuntimeException('YouTube OAuth: '.$response->body());
        }

        return (string) $response->json('access_token');
    }

    /** @param  array<string, mixed>  $payload */
    private function parseAnalytics(array $payload): array
    {
        $headers = $payload['columnHeaders'] ?? [];
        $rows = [];
        $index = [];
        foreach ($headers as $i => $header) {
            $index[$header['name'] ?? $i] = $i;
        }

        $daily = [];
        foreach ($payload['rows'] ?? [] as $row) {
            $date = $row[$index['day'] ?? 0] ?? now()->toDateString();
            $views = (int) ($row[$index['views'] ?? 1] ?? 0);
            $subs = (int) ($row[$index['subscribersGained'] ?? 2] ?? 0);
            $likes = (int) ($row[$index['likes'] ?? 3] ?? 0);
            $comments = (int) ($row[$index['comments'] ?? 4] ?? 0);
            $watchMin = (int) ($row[$index['estimatedMinutesWatched'] ?? 5] ?? 0);
            $retention = (int) ($row[$index['averageViewDuration'] ?? 6] ?? 0);

            $daily[] = [
                'stat_date' => $date,
                'impressions' => 0,
                'clicks' => 0,
                'leads' => 0,
                'applications' => 0,
                'views' => $views,
                'subscribers' => $subs,
                'likes' => $likes,
                'comments' => $comments,
                'spend' => null,
                'revenue' => null,
                'source_type' => 'organic',
                'metadata' => [
                    'collector' => 'youtube',
                    'watch_minutes' => $watchMin,
                    'avg_view_duration_sec' => $retention,
                ],
            ];
        }

        return ['rows' => $daily, 'campaigns' => []];
    }
}
