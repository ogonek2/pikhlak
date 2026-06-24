<?php

namespace App\Http\Controllers\Admin\ClientPortal;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\TrafficChannel;
use App\Services\Analytics\AnalyticsCollectorFactory;
use App\Services\Analytics\TrafficSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TrafficChannelController extends Controller
{
    public function __construct(
        private readonly AnalyticsCollectorFactory $collectors,
        private readonly TrafficSyncService $sync,
    ) {}

    public function index(Request $request): View
    {
        /** @var Project $project */
        $project = $request->attributes->get('project');
        $this->ensureChannels($project);

        $channels = $project->trafficChannels()->orderBy('name')->get();
        $statuses = config('analytics.connection_statuses', []);

        return view('admin.client.traffic.index', [
            'channels' => $channels,
            'statuses' => $statuses,
        ]);
    }

    public function show(Request $request, TrafficChannel $channel): View
    {
        $this->ensureProjectChannel($request, $channel);
        $platform = $this->collectors->platformConfig($channel->slug) ?? [];
        $collector = $this->collectors->forChannel($channel);

        return view('admin.client.traffic.show', [
            'channel' => $channel->load(['syncLogs' => fn ($q) => $q->latest()->limit(10)]),
            'platform' => $platform,
            'statuses' => config('analytics.connection_statuses', []),
            'isConfigured' => $collector->isConfigured($channel),
            'recentStats' => $channel->stats()->orderByDesc('stat_date')->limit(14)->get(),
            'campaigns' => $channel->campaignStats()->orderByDesc('stat_date')->limit(10)->get(),
        ]);
    }

    public function updateCredentials(Request $request, TrafficChannel $channel): RedirectResponse
    {
        $this->ensureProjectChannel($request, $channel);
        $fields = config("analytics.platforms.{$channel->slug}.credential_fields", []);

        $rules = [];
        foreach ($fields as $field) {
            $key = $field['key'];
            $rules["credentials.{$key}"] = [($field['required'] ?? false) ? 'required' : 'nullable', 'string', 'max:500'];
        }

        $data = $request->validate($rules);
        $incoming = $data['credentials'] ?? [];

        $merged = array_merge($channel->credentials ?? [], array_filter($incoming, fn ($v) => $v !== null && $v !== ''));

        $channel->update([
            'credentials' => $merged,
            'connection_status' => $merged !== [] ? 'configured' : 'disconnected',
        ]);

        return back()->with('success', 'Ключи API сохранены (зашифрованы в БД). Запустите синхронизацию.');
    }

    public function sync(Request $request, TrafficChannel $channel): RedirectResponse
    {
        $this->ensureProjectChannel($request, $channel);
        $days = (int) $request->input('days', config('analytics.sync_default_days', 30));
        $log = $this->sync->sync($channel, $days);

        if ($log->status === 'success') {
            return back()->with('success', $log->message ?? 'Синхронизация завершена.');
        }

        return back()->with('error', $log->message ?? 'Ошибка синхронизации.');
    }

    public function syncAll(Request $request): RedirectResponse
    {
        /** @var Project $project */
        $project = $request->attributes->get('project');
        $count = $this->sync->syncProject($project->id);

        return back()->with('success', "Синхронизировано каналов: {$count}.");
    }

    private function ensureChannels(Project $project): void
    {
        foreach (config('analytics.platforms', []) as $slug => $platform) {
            if (isset($platform['inherits'])) {
                continue;
            }
            TrafficChannel::query()->firstOrCreate(
                ['project_id' => $project->id, 'slug' => $slug],
                ['name' => $platform['name'], 'is_active' => true]
            );
        }
    }

    private function ensureProjectChannel(Request $request, TrafficChannel $channel): void
    {
        if ($channel->project_id !== $request->attributes->get('project')->id) {
            abort(404);
        }
    }
}
