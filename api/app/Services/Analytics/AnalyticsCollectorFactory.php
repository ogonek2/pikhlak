<?php

namespace App\Services\Analytics;

use App\Models\TrafficChannel;
use App\Services\Analytics\Contracts\AnalyticsCollectorInterface;
use App\Services\Analytics\Collectors\MetaAnalyticsCollector;
use App\Services\Analytics\Collectors\OlxAnalyticsCollector;
use App\Services\Analytics\Collectors\TikTokAnalyticsCollector;
use App\Services\Analytics\Collectors\YouTubeAnalyticsCollector;
use InvalidArgumentException;

class AnalyticsCollectorFactory
{
    public function forChannel(TrafficChannel $channel): AnalyticsCollectorInterface
    {
        $platform = config("analytics.platforms.{$channel->slug}.collector", $channel->slug);

        return match ($platform) {
            'meta' => app(MetaAnalyticsCollector::class),
            'tiktok' => app(TikTokAnalyticsCollector::class),
            'youtube' => app(YouTubeAnalyticsCollector::class),
            'olx' => app(OlxAnalyticsCollector::class),
            default => throw new InvalidArgumentException("Нет коллектора для канала: {$channel->slug}"),
        };
    }

    /** @return array<string, mixed>|null */
    public function platformConfig(string $slug): ?array
    {
        return config("analytics.platforms.{$slug}");
    }
}
