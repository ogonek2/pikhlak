@extends('admin.layouts.app')
@section('title', 'Каналы трафика')
@section('content')
<div class="mb-8 flex flex-wrap items-start justify-between gap-4">
    <div>
        <a href="{{ route('admin.client.dashboard') }}" class="text-sm text-slate-500 hover:text-sky-400">← Дашборд</a>
        <h1 class="mt-2 text-2xl font-bold">Сбор аналитики</h1>
        <p class="text-slate-500">Meta · TikTok · YouTube · Instagram · OLX</p>
    </div>
    <form method="POST" action="{{ route('admin.client.traffic.sync-all') }}">
        @csrf
        <button type="submit" class="rounded-lg bg-sky-600 px-4 py-2 text-sm font-medium text-white hover:bg-sky-500">
            ⟳ Синхронизировать все
        </button>
    </form>
</div>

@if (config('analytics.demo_when_unconfigured'))
<div class="mb-6 rounded-lg border border-amber-500/30 bg-amber-500/10 px-4 py-3 text-sm text-amber-200">
    Без API-ключей синхронизация пишет <strong>демо-данные</strong> (ANALYTICS_DEMO_MODE=true). После ввода ключей — реальные отчёты.
</div>
@endif

<div class="grid gap-4 md:grid-cols-2">
    @foreach ($channels as $channel)
    @php
        $status = $statuses[$channel->connection_status] ?? $channel->connection_status;
        $statusColor = match($channel->connection_status) {
            'connected' => 'text-emerald-400 bg-emerald-500/20',
            'configured' => 'text-sky-400 bg-sky-500/20',
            'error' => 'text-red-400 bg-red-500/20',
            default => 'text-slate-400 bg-slate-700',
        };
    @endphp
    <a href="{{ route('admin.client.traffic.show', $channel) }}"
       class="block rounded-xl border border-slate-800 bg-slate-900 p-6 transition hover:border-sky-500/40 hover:bg-slate-800/80">
        <div class="mb-3 flex items-center justify-between gap-2">
            <h2 class="text-lg font-semibold">{{ $channel->name }}</h2>
            <span class="rounded-full px-2 py-0.5 text-xs {{ $statusColor }}">{{ $status }}</span>
        </div>
        @if ($channel->last_synced_at)
        <p class="text-xs text-slate-500">Синхр.: {{ $channel->last_synced_at->format('d.m.Y H:i') }}</p>
        @endif
        @if ($channel->last_sync_error)
        <p class="mt-1 text-xs text-red-400 line-clamp-2">{{ $channel->last_sync_error }}</p>
        @endif
        <p class="mt-3 text-sm text-sky-400">Настроить API →</p>
    </a>
    @endforeach
</div>
@endsection
