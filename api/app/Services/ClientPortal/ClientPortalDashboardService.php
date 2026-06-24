<?php

namespace App\Services\ClientPortal;

use App\Models\RentalClient;
use App\Models\RentalClientPayment;
use App\Models\TrafficChannel;
use App\Models\TrafficChannelStat;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ClientPortalDashboardService
{
    /** @return array<string, mixed> */
    public function stats(int $projectId): array
    {
        $today = now()->startOfDay();

        return [
            'active_clients' => RentalClient::query()
                ->where('project_id', $projectId)
                ->where('status', 'active')
                ->count(),
            'total_clients' => RentalClient::query()->where('project_id', $projectId)->count(),
            'payments_due' => RentalClientPayment::query()
                ->whereHas('client', fn ($q) => $q->where('project_id', $projectId))
                ->where('status', 'pending')
                ->where('due_date', '<=', $today->copy()->addDays(7))
                ->count(),
            'overdue_payments' => RentalClientPayment::query()
                ->whereHas('client', fn ($q) => $q->where('project_id', $projectId))
                ->where('status', 'overdue')
                ->count(),
            'insurance_expiring' => \App\Models\RentalClientInsurance::query()
                ->whereHas('client', fn ($q) => $q->where('project_id', $projectId))
                ->whereNotNull('valid_until')
                ->whereBetween('valid_until', [$today, $today->copy()->addDays(30)])
                ->count(),
            'maintenance_planned' => \App\Models\RentalClientMaintenance::query()
                ->whereHas('client', fn ($q) => $q->where('project_id', $projectId))
                ->where('status', 'planned')
                ->where('scheduled_at', '<=', $today->copy()->addDays(14))
                ->count(),
        ];
    }

    /** @return Collection<int, array{channel: TrafficChannel, totals: array<string, int|float>, series: array<int, array<string, mixed>>}> */
    public function trafficOverview(int $projectId, int $days = 30): Collection
    {
        $channels = TrafficChannel::query()
            ->where('project_id', $projectId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        if ($channels->isEmpty()) {
            return $this->demoTrafficOverview($days);
        }

        $hasStats = TrafficChannelStat::query()
            ->whereIn('traffic_channel_id', $channels->pluck('id'))
            ->exists();

        if (! $hasStats && config('analytics.demo_when_unconfigured')) {
            return $this->demoTrafficOverview($days);
        }

        $from = now()->subDays($days - 1)->startOfDay();

        return $channels->map(function (TrafficChannel $channel) use ($from, $days) {
            $stats = TrafficChannelStat::query()
                ->where('traffic_channel_id', $channel->id)
                ->where('stat_date', '>=', $from->toDateString())
                ->orderBy('stat_date')
                ->get();

            return [
                'channel' => $channel,
                'totals' => [
                    'impressions' => (int) $stats->sum('impressions'),
                    'clicks' => (int) $stats->sum('clicks'),
                    'leads' => (int) $stats->sum('leads'),
                    'views' => (int) $stats->sum('views'),
                    'spend' => (float) $stats->sum('spend'),
                    'revenue' => (float) $stats->sum('revenue'),
                ],
                'series' => $stats->map(fn ($s) => [
                    'date' => $s->stat_date->format('d.m'),
                    'leads' => $s->leads,
                    'clicks' => $s->clicks,
                ])->values()->all(),
            ];
        });
    }

    /** @return Collection<int, array{channel: object, totals: array<string, int|float>, series: array<int, array<string, mixed>>}> */
    private function demoTrafficOverview(int $days): Collection
    {
        $presets = config('client_portal.demo_channels', []);

        return collect($presets)->map(function (array $preset) use ($days) {
            $series = [];
            $totals = ['impressions' => 0, 'clicks' => 0, 'leads' => 0, 'views' => 0, 'spend' => 0.0, 'revenue' => 0.0];

            for ($i = $days - 1; $i >= 0; $i--) {
                $date = Carbon::now()->subDays($i);
                $leads = random_int($preset['leads_min'] ?? 1, $preset['leads_max'] ?? 5);
                $clicks = random_int($preset['clicks_min'] ?? 20, $preset['clicks_max'] ?? 80);
                $impressions = $clicks * random_int(8, 15);
                $spend = round($clicks * ($preset['cpc'] ?? 0.45), 2);

                $series[] = [
                    'date' => $date->format('d.m'),
                    'leads' => $leads,
                    'clicks' => $clicks,
                ];

                $totals['impressions'] += $impressions;
                $totals['clicks'] += $clicks;
                $totals['leads'] += $leads;
                $totals['views'] += random_int(100, 800);
                $totals['spend'] += $spend;
                $totals['revenue'] += $leads * ($preset['revenue_per_lead'] ?? 120);
            }

            return [
                'channel' => (object) [
                    'name' => $preset['name'],
                    'slug' => $preset['slug'],
                    'api_connected' => false,
                    'is_demo' => true,
                ],
                'totals' => $totals,
                'series' => $series,
            ];
        });
    }
}
