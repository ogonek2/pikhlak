@extends('admin.layouts.app')
@section('title', 'Реферальные ссылки')

@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold">Реферальные ссылки</h1>
        <p class="text-sm text-slate-500">Трекинг каналов, авто и посредников в Telegram</p>
    </div>
    <a href="{{ route('admin.referrals.create') }}" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-500">+ Создать ссылку</a>
</div>

@if (!$telegramReady)
    <div class="mb-4 rounded-lg border border-amber-500/30 bg-amber-500/10 px-4 py-3 text-sm text-amber-200">
        Ссылки ведут <strong>только</strong> на бота Pikhlak. Синхронизируйте токен:
        <code class="mt-1 block text-amber-100">php artisan pikhlak:sync-bot-token</code>
        (подтянется @username из Telegram API getMe)
    </div>
@else
    <div class="mb-4 rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-200">
        Все ссылки открывают <strong>@{{ $botUsername }}</strong> — вашего бота Pikhlak. Формат: <code class="text-emerald-100">https://t.me/{{ $botUsername }}?start=КОД</code>
    </div>
@endif

<div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
    <div class="rounded-xl border border-slate-800 bg-slate-900 p-4">
        <div class="text-xs text-slate-500">Переходы (/start)</div>
        <div class="text-2xl font-bold text-white">{{ $stats['starts'] }}</div>
    </div>
    <div class="rounded-xl border border-slate-800 bg-slate-900 p-4">
        <div class="text-xs text-slate-500">Лиды</div>
        <div class="text-2xl font-bold text-emerald-400">{{ $stats['leads'] }}</div>
    </div>
    <div class="rounded-xl border border-slate-800 bg-slate-900 p-4">
        <div class="text-xs text-slate-500">Конверсии</div>
        <div class="text-2xl font-bold text-violet-400">{{ $stats['conversions'] }}</div>
    </div>
    <div class="rounded-xl border border-slate-800 bg-slate-900 p-4">
        <div class="text-xs text-slate-500">CR в лид</div>
        <div class="text-2xl font-bold text-orange-300">{{ $stats['lead_rate'] }}%</div>
    </div>
    <div class="rounded-xl border border-slate-800 bg-slate-900 p-4">
        <div class="text-xs text-slate-500">Активных ссылок</div>
        <div class="text-2xl font-bold text-white">{{ $stats['active_links'] }}</div>
    </div>
</div>

@if ($stats['by_channel']->isNotEmpty())
<div class="mb-6 rounded-xl border border-slate-800 bg-slate-900 p-4">
    <h2 class="mb-3 text-sm font-semibold text-slate-400">По каналам</h2>
    <div class="flex flex-wrap gap-3">
        @foreach ($stats['by_channel'] as $row)
            @if ($row->channel)
            <div class="rounded-lg bg-slate-800 px-3 py-2 text-xs">
                <span class="font-medium text-white">{{ $channels[$row->channel] ?? $row->channel }}</span>
                <span class="text-slate-500"> — {{ $row->starts }} стартов, {{ $row->leads }} лидов</span>
            </div>
            @endif
        @endforeach
    </div>
</div>
@endif

<form method="GET" class="mb-4 flex flex-wrap gap-2 text-sm">
    <select name="type" class="rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-white">
        <option value="">Все типы</option>
        @foreach ($types as $k => $label)
            <option value="{{ $k }}" @selected($filterType === $k)>{{ $label }}</option>
        @endforeach
    </select>
    <select name="channel" class="rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-white">
        <option value="">Все каналы</option>
        @foreach ($channels as $k => $label)
            <option value="{{ $k }}" @selected($filterChannel === $k)>{{ $label }}</option>
        @endforeach
    </select>
    <label class="flex items-center gap-2 text-slate-400">
        <input type="checkbox" name="active_only" value="1" @checked(request('active_only')) class="rounded border-slate-600">
        Только активные
    </label>
    <button type="submit" class="rounded-lg border border-slate-700 px-4 py-2 text-slate-300 hover:bg-slate-800">Фильтр</button>
</form>

<div class="overflow-hidden rounded-xl border border-slate-800">
    <table class="w-full text-left text-sm">
        <thead class="bg-slate-900 text-slate-500">
        <tr>
            <th class="px-4 py-3">Название</th>
            <th class="px-4 py-3">Тип / канал</th>
            <th class="px-4 py-3">Код</th>
            <th class="px-4 py-3 text-center">Старты</th>
            <th class="px-4 py-3 text-center">Лиды</th>
            <th class="px-4 py-3 text-center">CR</th>
            <th class="px-4 py-3"></th>
        </tr>
        </thead>
        <tbody class="divide-y divide-slate-800">
        @forelse ($links as $link)
            @php
                $url = ($telegramReady && $bot) ? app(\App\Services\Referral\ReferralLinkBuilder::class)->telegramUrl($bot, $link) : null;
            @endphp
            <tr class="bg-slate-900/30 {{ !$link->is_active ? 'opacity-60' : '' }}">
                <td class="px-4 py-3">
                    <div class="font-medium text-white">{{ $link->name }}</div>
                    @if ($link->car)
                        <div class="text-xs text-slate-500">{{ $link->car->make }} {{ $link->car->model }}</div>
                    @elseif ($link->partner_name)
                        <div class="text-xs text-slate-500">{{ $link->partner_name }}</div>
                    @endif
                </td>
                <td class="px-4 py-3">
                    <span class="text-xs text-violet-300">{{ $link->typeLabel() }}</span>
                    @if ($link->channel)
                        <div class="text-xs text-slate-500">{{ $link->channelLabel() }}</div>
                    @endif
                </td>
                <td class="px-4 py-3 font-mono text-xs text-emerald-400">{{ $link->code }}</td>
                <td class="px-4 py-3 text-center">{{ $link->starts_count }}</td>
                <td class="px-4 py-3 text-center text-emerald-400">{{ $link->leads_count }}</td>
                <td class="px-4 py-3 text-center">{{ $link->conversionRate() }}%</td>
                <td class="px-4 py-3 text-right">
                    @if ($url)
                        <button type="button" onclick="navigator.clipboard.writeText('{{ $url }}')" class="text-xs text-slate-400 hover:text-white" title="Копировать">📋</button>
                    @endif
                    <a href="{{ route('admin.referrals.show', $link) }}" class="ml-2 text-xs text-emerald-400 hover:underline">Стат.</a>
                    <a href="{{ route('admin.referrals.edit', $link) }}" class="ml-2 text-xs text-slate-400 hover:underline">Изм.</a>
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="px-4 py-8 text-center text-slate-500">Ссылок пока нет — создайте первую для Instagram, TikTok или конкретного авто</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $links->links() }}</div>
@endsection
