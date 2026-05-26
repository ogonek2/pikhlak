@extends('admin.layouts.app')
@section('title', $referralLink->name)

@section('content')
<div class="mb-6 flex flex-wrap items-start justify-between gap-4">
    <div>
        <a href="{{ route('admin.referrals.index') }}" class="text-sm text-slate-500 hover:text-emerald-400">← К списку</a>
        <h1 class="mt-2 text-2xl font-bold">{{ $referralLink->name }}</h1>
        <p class="text-sm text-slate-500">{{ $referralLink->typeLabel() }} @if($referralLink->channel) · {{ $referralLink->channelLabel() }} @endif</p>
    </div>
    <a href="{{ route('admin.referrals.edit', $referralLink) }}" class="rounded-lg border border-slate-700 px-4 py-2 text-sm text-slate-300 hover:bg-slate-800">Редактировать</a>
</div>

@if ($telegramUrl && $botUsername)
<div class="mb-6 rounded-xl border border-emerald-500/30 bg-emerald-500/5 p-4">
    <div class="text-xs font-semibold uppercase text-emerald-400">Ссылка на бота @{{ $botUsername }} (только Pikhlak)</div>
    <div class="mt-2 flex flex-wrap items-center gap-3">
        <code class="break-all text-sm text-white">{{ $telegramUrl }}</code>
        <button type="button" onclick="navigator.clipboard.writeText('{{ $telegramUrl }}')"
                class="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs text-white hover:bg-emerald-500">Копировать</button>
    </div>
    <div class="mt-1 font-mono text-xs text-slate-500">Код: {{ $referralLink->code }}</div>
</div>
@endif

<div class="mb-6 grid gap-4 sm:grid-cols-4">
    <div class="rounded-xl border border-slate-800 bg-slate-900 p-4 text-center">
        <div class="text-2xl font-bold">{{ $referralLink->starts_count }}</div>
        <div class="text-xs text-slate-500">Переходов /start</div>
    </div>
    <div class="rounded-xl border border-slate-800 bg-slate-900 p-4 text-center">
        <div class="text-2xl font-bold text-emerald-400">{{ $referralLink->leads_count }}</div>
        <div class="text-xs text-slate-500">Лидов</div>
    </div>
    <div class="rounded-xl border border-slate-800 bg-slate-900 p-4 text-center">
        <div class="text-2xl font-bold text-violet-400">{{ $referralLink->conversions_count }}</div>
        <div class="text-xs text-slate-500">Конверсий</div>
    </div>
    <div class="rounded-xl border border-slate-800 bg-slate-900 p-4 text-center">
        <div class="text-2xl font-bold text-orange-300">{{ $referralLink->conversionRate() }}%</div>
        <div class="text-xs text-slate-500">CR старт → лид</div>
    </div>
</div>

@if ($referralLink->description)
<p class="mb-4 text-sm text-slate-400">{{ $referralLink->description }}</p>
@endif

<div class="grid gap-6 lg:grid-cols-2">
    <div class="rounded-xl border border-slate-800 bg-slate-900 p-4">
        <h2 class="mb-3 font-semibold">Лиды по ссылке</h2>
        <ul class="space-y-2 text-sm">
            @forelse ($leads as $lead)
                <li class="flex justify-between border-b border-slate-800 py-2">
                    <span class="text-slate-300">{{ Str::limit($lead->uuid, 8) }} · {{ $lead->status?->name ?? '—' }}</span>
                    <span class="text-slate-500">{{ $lead->created_at?->format('d.m.Y') }}</span>
                </li>
            @empty
                <li class="text-slate-500">Лидов пока нет</li>
            @endforelse
        </ul>
    </div>
    <div class="rounded-xl border border-slate-800 bg-slate-900 p-4">
        <h2 class="mb-3 font-semibold">События</h2>
        <ul class="max-h-80 space-y-1 overflow-y-auto text-xs">
            @forelse ($events as $ev)
                <li class="flex justify-between py-1 text-slate-400">
                    <span><span class="text-emerald-400">{{ $ev->event_type }}</span> @if($ev->lead_id) #{{ $ev->lead_id }} @endif</span>
                    <span>{{ $ev->created_at?->format('d.m H:i') }}</span>
                </li>
            @empty
                <li class="text-slate-500">Событий нет</li>
            @endforelse
        </ul>
    </div>
</div>
@endsection
