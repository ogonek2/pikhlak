@extends('admin.layouts.app')

@section('title', 'Настройки бота')

@section('content')
<div class="mb-8">
    <h1 class="text-2xl font-bold">Настройки бота</h1>
    <p class="text-slate-500">Токен, webhook, статус</p>
</div>

<form method="POST" action="{{ route('admin.bot.update') }}" class="max-w-2xl space-y-6 rounded-xl border border-slate-800 bg-slate-900 p-6">
    @csrf
    @method('PUT')

    <div>
        <label class="mb-1 block text-sm text-slate-400">Название</label>
        <input type="text" name="name" value="{{ old('name', $bot->name) }}" required
               class="w-full rounded-lg border border-slate-700 bg-slate-800 px-4 py-2 text-white">
    </div>

    <div>
        <label class="mb-1 block text-sm text-slate-400">Режим</label>
        <select name="mode" class="w-full rounded-lg border border-slate-700 bg-slate-800 px-4 py-2 text-white">
            <option value="polling" @selected($bot->mode === 'polling')>Polling (dev — Node bot)</option>
            <option value="webhook" @selected($bot->mode === 'webhook')>Webhook (production)</option>
        </select>
    </div>

    <div>
        <label class="mb-1 block text-sm text-slate-400">Webhook secret</label>
        <input type="text" name="webhook_secret" value="{{ old('webhook_secret', $bot->webhook_secret) }}"
               class="w-full rounded-lg border border-slate-700 bg-slate-800 px-4 py-2 font-mono text-sm text-white">
    </div>

    <div>
        <label class="mb-1 block text-sm text-slate-400">Telegram token</label>
        <input type="text" readonly value="{{ $bot->telegram_token ? '••••••••' . substr($bot->telegram_token, -8) : 'Не задан' }}"
               class="w-full rounded-lg border border-slate-700 bg-slate-800/50 px-4 py-2 font-mono text-sm text-slate-500">
        <p class="mt-1 text-xs text-slate-600">Задаётся в .env → <code class="text-emerald-500">php artisan pikhlak:sync-bot-token</code></p>
    </div>

    @php $tgUser = $bot->config['telegram_username'] ?? null; @endphp
    <div>
        <label class="mb-1 block text-sm text-slate-400">Бот в Telegram (для реф. ссылок)</label>
        <input type="text" readonly value="{{ $tgUser ? '@'.$tgUser : 'Не синхронизирован — выполните sync-bot-token' }}"
               class="w-full rounded-lg border border-slate-700 bg-slate-800/50 px-4 py-2 text-white">
        @if ($tgUser)
            <p class="mt-1 text-xs text-slate-500">Реферальные ссылки ведут сюда: <code>https://t.me/{{ $tgUser }}?start=...</code></p>
        @endif
    </div>

    <label class="flex items-center gap-2">
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $bot->is_active))
               class="rounded border-slate-600 bg-slate-800 text-emerald-500">
        <span class="text-sm">Бот активен</span>
    </label>

    <button type="submit" class="rounded-lg bg-emerald-600 px-6 py-2.5 font-semibold text-white hover:bg-emerald-500">
        Сохранить
    </button>
</form>
@endsection
