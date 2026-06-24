@extends('admin.layouts.app')
@section('title', $channel->name)
@section('content')
<div class="mb-8">
    <a href="{{ route('admin.client.traffic.index') }}" class="text-sm text-slate-500 hover:text-sky-400">← Каналы</a>
    <h1 class="mt-2 text-2xl font-bold">{{ $channel->name }}</h1>
    <p class="text-slate-500">
        @foreach ($platform['apis'] ?? [] as $api)
            <span class="mr-2 rounded bg-slate-800 px-2 py-0.5 text-xs">{{ $api }}</span>
        @endforeach
    </p>
    @if (!empty($platform['docs_url']))
    <a href="{{ $platform['docs_url'] }}" target="_blank" rel="noopener" class="mt-1 inline-block text-sm text-sky-400 hover:underline">Документация API</a>
    @endif
</div>

<div class="mb-8 grid gap-6 lg:grid-cols-2">
    <div class="rounded-xl border border-slate-800 bg-slate-900 p-6">
        <h2 class="mb-4 font-semibold">Что можно собирать</h2>
        @if (!empty($platform['metrics_paid']))
        <p class="mb-2 text-xs font-semibold uppercase text-slate-500">Реклама / платное</p>
        <ul class="mb-4 list-inside list-disc text-sm text-slate-300">
            @foreach ($platform['metrics_paid'] as $label)
            <li>{{ $label }}</li>
            @endforeach
        </ul>
        @endif
        @if (!empty($platform['metrics_organic']))
        <p class="mb-2 text-xs font-semibold uppercase text-slate-500">Органика / контент</p>
        <ul class="list-inside list-disc text-sm text-slate-300">
            @foreach ($platform['metrics_organic'] as $label)
            <li>{{ $label }}</li>
            @endforeach
        </ul>
        @endif
        @if (!empty($platform['notes']))
        <p class="mt-4 text-xs text-amber-400/90">{{ $platform['notes'] }}</p>
        @endif
    </div>

    <div class="rounded-xl border border-slate-800 bg-slate-900 p-6">
        <h2 class="mb-4 font-semibold">Подключение API</h2>
        <form method="POST" action="{{ route('admin.client.traffic.credentials', $channel) }}" class="space-y-4">
            @csrf @method('PUT')
            @foreach ($platform['credential_fields'] ?? [] as $field)
            <div>
                <label class="mb-1 block text-sm text-slate-400">
                    {{ $field['label'] }}
                    @if ($field['required'] ?? false)<span class="text-red-400">*</span>@endif
                </label>
                <input type="{{ $field['type'] ?? 'text' }}"
                       name="credentials[{{ $field['key'] }}]"
                       value=""
                       placeholder="{{ ($channel->credentials[$field['key']] ?? null) ? '•••••••• (оставьте пустым, чтобы не менять)' : '' }}"
                       class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-white">
            </div>
            @endforeach
            <button type="submit" class="rounded-lg bg-sky-600 px-4 py-2 text-sm font-medium text-white hover:bg-sky-500">Сохранить ключи</button>
        </form>

        <form method="POST" action="{{ route('admin.client.traffic.sync', $channel) }}" class="mt-4 flex flex-wrap items-center gap-3 border-t border-slate-800 pt-4">
            @csrf
            <label class="text-sm text-slate-400">Дней:</label>
            <input type="number" name="days" value="30" min="1" max="90" class="w-20 rounded border border-slate-700 bg-slate-800 px-2 py-1 text-sm text-white">
            <button type="submit" class="rounded-lg border border-sky-500/50 px-4 py-2 text-sm text-sky-300 hover:bg-sky-500/10">⟳ Синхронизировать</button>
        </form>

        <dl class="mt-4 space-y-1 text-xs text-slate-500">
            <div>Статус: <span class="text-slate-300">{{ $statuses[$channel->connection_status] ?? $channel->connection_status }}</span></div>
            @if ($channel->last_synced_at)
            <div>Последняя синхр.: {{ $channel->last_synced_at->format('d.m.Y H:i') }}</div>
            @endif
        </dl>
    </div>
</div>

@if ($recentStats->isNotEmpty())
<div class="mb-8 rounded-xl border border-slate-800 bg-slate-900 p-6">
    <h2 class="mb-4 font-semibold">Данные за последние 14 дней</h2>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="text-slate-500">
                <tr>
                    <th class="py-2 pr-4">Дата</th>
                    <th class="py-2 pr-4">Показы</th>
                    <th class="py-2 pr-4">Клики</th>
                    <th class="py-2 pr-4">Лиды</th>
                    <th class="py-2 pr-4">Просмотры</th>
                    <th class="py-2 pr-4">Расход</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800 text-slate-300">
                @foreach ($recentStats as $s)
                <tr>
                    <td class="py-2 pr-4">{{ $s->stat_date->format('d.m') }}</td>
                    <td class="py-2 pr-4">{{ number_format($s->impressions) }}</td>
                    <td class="py-2 pr-4">{{ number_format($s->clicks) }}</td>
                    <td class="py-2 pr-4">{{ $s->leads }}</td>
                    <td class="py-2 pr-4">{{ $s->views ? number_format($s->views) : '—' }}</td>
                    <td class="py-2 pr-4">{{ $s->spend ? '$'.number_format($s->spend, 2) : '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@if ($campaigns->isNotEmpty())
<div class="rounded-xl border border-slate-800 bg-slate-900 p-6">
    <h2 class="mb-4 font-semibold">Кампании (последние)</h2>
    <div class="space-y-2 text-sm">
        @foreach ($campaigns as $c)
        <div class="flex flex-wrap justify-between gap-2 rounded-lg border border-slate-800 px-3 py-2">
            <span>{{ $c->name }}</span>
            <span class="text-slate-500">{{ $c->stat_date->format('d.m') }} · клики {{ $c->clicks }} · лиды {{ $c->leads }}</span>
        </div>
        @endforeach
    </div>
</div>
@endif

@if ($channel->syncLogs->isNotEmpty())
<div class="mt-8 rounded-xl border border-slate-800 bg-slate-900 p-6">
    <h2 class="mb-4 font-semibold">Журнал синхронизаций</h2>
    <div class="space-y-2 text-sm">
        @foreach ($channel->syncLogs as $log)
        <div class="flex justify-between text-slate-400">
            <span>{{ $log->created_at->format('d.m.Y H:i') }} — {{ $log->status }}</span>
            <span>{{ $log->message }}</span>
        </div>
        @endforeach
    </div>
</div>
@endif
@endsection
