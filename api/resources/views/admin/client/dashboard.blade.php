@extends('admin.layouts.app')
@section('title', 'Клиентский портал')
@section('content')
<div class="mb-8 flex flex-wrap items-start justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-white">Клиентский бот</h1>
        <p class="text-slate-500">Уведомления действующих клиентов · аренда · платежи · ТО</p>
    </div>
    <a href="{{ route('admin.client.bot.show') }}"
       class="rounded-lg bg-sky-600 px-4 py-2 text-sm font-medium text-white hover:bg-sky-500">
        Настроить токен бота
    </a>
    @if (($pendingManagerRequests ?? 0) > 0)
    <a href="{{ route('admin.client.bot.manager-requests') }}"
       class="rounded-lg border border-amber-500/40 bg-amber-500/10 px-4 py-2 text-sm font-medium text-amber-300 hover:bg-amber-500/20">
        Запросы менеджера ({{ $pendingManagerRequests }})
    </a>
    @endif
</div>

<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
    @foreach ([
        ['Активные клиенты', $stats['active_clients'], 'sky'],
        ['Всего в базе', $stats['total_clients'], 'slate'],
        ['Платежи (7 дн.)', $stats['payments_due'], 'amber'],
        ['Просрочено', $stats['overdue_payments'], 'red'],
        ['Страховки (30 дн.)', $stats['insurance_expiring'], 'violet'],
        ['ТО (14 дн.)', $stats['maintenance_planned'], 'emerald'],
    ] as [$label, $value, $color])
    <div class="rounded-xl border border-slate-800 bg-slate-900 p-5">
        <div class="text-sm text-slate-500">{{ $label }}</div>
        <div class="mt-2 text-3xl font-bold text-white">{{ $value }}</div>
    </div>
    @endforeach
</div>

<div class="mt-8 grid gap-6 lg:grid-cols-3">
    <div class="rounded-xl border border-slate-800 bg-slate-900 p-6 lg:col-span-2">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-lg font-semibold">Трафик по каналам (30 дней)</h2>
            @if ($isDemoTraffic)
            <span class="rounded-full bg-amber-500/20 px-3 py-1 text-xs text-amber-300">Демо-данные · API подключим позже</span>
            @endif
        </div>
        <div class="space-y-4">
            @foreach ($traffic as $row)
            @php $ch = $row['channel']; $t = $row['totals']; @endphp
            <div class="rounded-lg border border-slate-800 bg-slate-950/50 p-4">
                <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                    <div class="font-medium text-white">{{ $ch->name }}</div>
                    <div class="flex gap-2 text-xs">
                        @if (!($ch->api_connected ?? false))
                        <span class="rounded bg-slate-800 px-2 py-0.5 text-slate-400">API не подключён</span>
                        @else
                        <span class="rounded bg-emerald-500/20 px-2 py-0.5 text-emerald-400">API активен</span>
                        @endif
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3 text-sm sm:grid-cols-5">
                    <div><span class="text-slate-500">Показы</span><div class="font-semibold">{{ number_format($t['impressions']) }}</div></div>
                    <div><span class="text-slate-500">Клики</span><div class="font-semibold">{{ number_format($t['clicks']) }}</div></div>
                    <div><span class="text-slate-500">Лиды</span><div class="font-semibold text-sky-400">{{ number_format($t['leads']) }}</div></div>
                    <div><span class="text-slate-500">Просмотры</span><div class="font-semibold">{{ number_format($t['views'] ?? 0) }}</div></div>
                    <div><span class="text-slate-500">Расход</span><div class="font-semibold">${{ number_format($t['spend'], 0) }}</div></div>
                </div>
            </div>
            @endforeach
        </div>
        <p class="mt-3 text-xs text-slate-600">* Выручка — оценочная, до подключения реальных API Meta, TikTok, YouTube, Instagram, OLX.</p>
    </div>

    <div class="space-y-6">
        <div class="rounded-xl border border-slate-800 bg-slate-900 p-6">
            <h2 class="mb-4 text-lg font-semibold">Клиентский бот</h2>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-slate-500">Статус</dt>
                    <dd class="{{ $bot->is_active ? 'text-emerald-400' : 'text-red-400' }}">{{ $bot->is_active ? 'Активен' : 'Выключен' }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Токен</dt>
                    <dd>{{ $bot->telegram_token ? 'Задан' : 'Не задан' }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Username</dt>
                    <dd>{{ $bot->config['telegram_username'] ?? '—' }}</dd></div>
            </dl>
            <a href="{{ route('admin.client.bot.show') }}" class="mt-4 inline-block text-sm text-sky-400 hover:underline">Настройки →</a>
        </div>
        <div class="rounded-xl border border-slate-800 bg-slate-900 p-6">
            <h2 class="mb-4 text-lg font-semibold">Быстрые действия</h2>
            <div class="flex flex-col gap-2">
                <a href="{{ route('admin.client.clients.create') }}" class="rounded-lg bg-sky-600 px-4 py-2 text-center text-sm text-white hover:bg-sky-500">+ Новый клиент</a>
                <a href="{{ route('admin.client.clients.index') }}" class="rounded-lg border border-slate-700 px-4 py-2 text-center text-sm text-slate-300 hover:bg-slate-800">База клиентов</a>
                <a href="{{ route('admin.client.traffic.index') }}" class="rounded-lg border border-slate-700 px-4 py-2 text-center text-sm text-slate-300 hover:bg-slate-800">Каналы трафика</a>
            </div>
        </div>
    </div>
</div>
@endsection
