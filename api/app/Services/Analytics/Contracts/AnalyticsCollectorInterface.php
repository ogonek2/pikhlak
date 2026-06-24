<?php

namespace App\Services\Analytics\Contracts;

use App\Models\TrafficChannel;
use Carbon\Carbon;

interface AnalyticsCollectorInterface
{
    public function platform(): string;

    /** @return array{rows: list<array<string, mixed>>, campaigns: list<array<string, mixed>>} */
    public function fetch(TrafficChannel $channel, Carbon $from, Carbon $to): array;

    public function isConfigured(TrafficChannel $channel): bool;
}
