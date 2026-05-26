@extends('admin.layouts.app')

@section('title', 'Дашборд')

@section('content')
<div class="mb-8">
    <h1 class="text-2xl font-bold text-white">Дашборд</h1>
    <p class="text-slate-500">Управление Telegram-ботом Pikhlak Auto</p>
</div>

@if (($stats['operator_requests'] ?? 0) > 0)
    <a href="{{ route('admin.chats.index', ['filter' => 'operator']) }}"
       class="mb-6 flex items-center gap-4 rounded-xl border border-orange-500/40 bg-orange-500/10 px-6 py-4 transition hover:bg-orange-500/15">
        <span class="flex h-12 w-12 items-center justify-center rounded-full bg-orange-500/20 text-2xl">📞</span>
        <div class="flex-1">
            <div class="font-semibold text-orange-200">Клиенты ждут оператора</div>
            <div class="text-sm text-orange-300/80">{{ $stats['operator_requests'] }} активных запросов — откройте чаты и ответьте вручную</div>
        </div>
        <span class="text-orange-400">→</span>
    </a>
@endif

<div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
    <div class="rounded-xl border border-slate-800 bg-slate-900 p-6">
        <div class="text-sm text-slate-500">Чаты</div>
        <div class="mt-2 text-3xl font-bold text-white">{{ $stats['chats'] }}</div>
        <a href="{{ route('admin.chats.index') }}" class="mt-2 inline-block text-xs text-emerald-400 hover:underline">Открыть переписки</a>
    </div>
    <div class="rounded-xl border border-slate-800 bg-slate-900 p-6">
        <div class="text-sm text-slate-500">Лиды</div>
        <div class="mt-2 text-3xl font-bold text-white">{{ $stats['leads'] }}</div>
    </div>
    <div class="rounded-xl border {{ ($stats['operator_requests'] ?? 0) > 0 ? 'border-orange-500/40 bg-orange-500/5' : 'border-slate-800 bg-slate-900' }} p-6">
        <div class="text-sm {{ ($stats['operator_requests'] ?? 0) > 0 ? 'text-orange-400' : 'text-slate-500' }}">Запросы оператора</div>
        <div class="mt-2 text-3xl font-bold {{ ($stats['operator_requests'] ?? 0) > 0 ? 'text-orange-300' : 'text-white' }}">{{ $stats['operator_requests'] ?? 0 }}</div>
        @if (($stats['operator_requests'] ?? 0) > 0)
            <a href="{{ route('admin.leads.index', ['filter' => 'operator']) }}" class="mt-2 inline-block text-xs text-orange-400 hover:underline">В таблице лидов</a>
        @endif
    </div>
    <div class="rounded-xl border border-orange-500/30 bg-orange-500/5 p-6">
        <div class="text-sm text-orange-400">🔥 Hot leads</div>
        <div class="mt-2 text-3xl font-bold text-orange-300">{{ $stats['hot_leads'] }}</div>
    </div>
    <div class="rounded-xl border border-slate-800 bg-slate-900 p-6">
        <div class="text-sm text-slate-500">FAQ</div>
        <div class="mt-2 text-3xl font-bold text-white">{{ $stats['faq'] }}</div>
    </div>
    <div class="rounded-xl border border-slate-800 bg-slate-900 p-6">
        <div class="text-sm text-slate-500">Бот</div>
        <div class="mt-2 text-lg font-semibold {{ $bot?->is_active ? 'text-emerald-400' : 'text-red-400' }}">
            {{ $bot?->is_active ? 'Активен' : 'Выключен' }}
        </div>
    </div>
</div>

<div class="mt-8 grid gap-6 lg:grid-cols-2">
    <div class="rounded-xl border border-slate-800 bg-slate-900 p-6">
        <h2 class="mb-4 text-lg font-semibold">Быстрые действия</h2>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('admin.referrals.index') }}" class="rounded-lg bg-violet-600 px-4 py-2 text-sm font-medium text-white hover:bg-violet-500">Реф. ссылки</a>
            <a href="{{ route('admin.chats.index') }}" class="rounded-lg border border-slate-700 px-4 py-2 text-sm text-slate-300 hover:bg-slate-800">Переписки</a>
            <a href="{{ route('admin.bot.messages') }}" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-500">Редактировать тексты</a>
            <a href="{{ route('admin.bot.show') }}" class="rounded-lg border border-slate-700 px-4 py-2 text-sm text-slate-300 hover:bg-slate-800">Настройки бота</a>
            <a href="{{ route('admin.ai.index') }}" class="rounded-lg border border-slate-700 px-4 py-2 text-sm text-slate-300 hover:bg-slate-800">ИИ центр</a>
        </div>
    </div>
    @if ($bot)
    <div class="rounded-xl border border-slate-800 bg-slate-900 p-6">
        <h2 class="mb-4 text-lg font-semibold">Информация о боте</h2>
        <dl class="space-y-2 text-sm">
            <div class="flex justify-between"><dt class="text-slate-500">Имя</dt><dd>{{ $bot->name }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">UUID</dt><dd class="font-mono text-xs text-slate-400">{{ $bot->uuid }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">Режим</dt><dd>{{ $bot->mode }}</dd></div>
        </dl>
    </div>
    @endif
</div>
@endsection
