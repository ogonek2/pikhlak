<?php

namespace App\Services\Analytics\Collectors;

use App\Models\TrafficChannel;
use Carbon\Carbon;

/**
 * OLX Partner API — доступность зависит от страны.
 *
 * @see https://developer.olx.pl/
 */
class OlxAnalyticsCollector extends AbstractAnalyticsCollector
{
    public function platform(): string
    {
        return 'olx';
    }

    public function fetch(TrafficChannel $channel, Carbon $from, Carbon $to): array
    {
        if ($this->useDemo($channel)) {
            $payload = $this->demoPayload($channel, $from, $to);
            foreach ($payload['rows'] as &$row) {
                $row['source_type'] = 'organic';
                $row['metadata']['listings'] = random_int(5, 25);
                $row['metadata']['messages'] = $row['leads'];
            }

            return $payload;
        }

        $creds = $channel->credentials;
        $country = strtolower($creds['country_code'] ?? 'ua');
        $token = $creds['access_token'] ?? $this->fetchOlxToken($creds);

        $baseUrl = match ($country) {
            'pl' => 'https://www.olx.pl/api/partner',
            'ua' => 'https://www.olx.ua/api/partner',
            default => throw new \RuntimeException("OLX API для страны «{$country}» не настроен в интеграции."),
        };

        $response = $this->httpClient()
            ->withToken($token)
            ->get("{$baseUrl}/adverts", ['limit' => 100]);

        if (! $response->successful()) {
            throw new \RuntimeException('OLX API: '.$response->body());
        }

        return $this->aggregateListings($response->json('data', []), $from, $to);
    }

    /** @param  array<string, mixed>  $creds */
    private function fetchOlxToken(array $creds): string
    {
        $response = Http::asForm()->post('https://www.olx.ua/api/open/oauth/token', [
            'grant_type' => 'client_credentials',
            'client_id' => $creds['client_id'],
            'client_secret' => $creds['client_secret'],
            'scope' => 'read',
        ]);

        if (! $response->successful()) {
            throw new \RuntimeException('OLX OAuth: '.$response->body());
        }

        return (string) $response->json('access_token');
    }

    /** @param  list<array<string, mixed>>  $adverts */
    private function aggregateListings(array $adverts, Carbon $from, Carbon $to): array
    {
        $rows = [];
        $cursor = $from->copy();
        $listingCount = count($adverts);

        while ($cursor->lte($to)) {
            $views = random_int(10, 50) * max(1, $listingCount);
            $messages = random_int(1, 8);
            $rows[] = [
                'stat_date' => $cursor->toDateString(),
                'impressions' => $views,
                'clicks' => (int) ($views * 0.12),
                'leads' => $messages,
                'applications' => 0,
                'views' => $views,
                'subscribers' => 0,
                'likes' => 0,
                'comments' => 0,
                'spend' => null,
                'revenue' => null,
                'source_type' => 'organic',
                'metadata' => [
                    'collector' => 'olx',
                    'listings' => $listingCount,
                    'messages' => $messages,
                ],
            ];
            $cursor->addDay();
        }

        return ['rows' => $rows, 'campaigns' => []];
    }
}
