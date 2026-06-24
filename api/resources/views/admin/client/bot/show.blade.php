@extends('admin.layouts.app')
@section('title', 'Клиентский бот')
@section('content')
@php
    $tokenSet = (bool) $bot->telegram_token;
    $tokenTail = $tokenSet ? substr($bot->telegram_token, -8) : null;
    $schedulerMin = config('client_bot.scheduler_interval_minutes', 5);
@endphp

<div class="mb-6 flex flex-wrap items-start justify-between gap-4">
    <div>
        <a href="{{ route('admin.client.dashboard') }}" class="text-sm text-slate-500 hover:text-sky-400">← Дашборд клиентов</a>
        <h1 class="mt-2 text-2xl font-bold">Клиентский бот</h1>
        <p class="mt-1 max-w-2xl text-sm text-slate-500">
            Уведомления арендаторам: платежи, ТО, страховка. Транспорт — папка <code class="text-sky-300">client-bot/</code>, логика и БД — Laravel API.
        </p>
    </div>
    <div class="flex flex-wrap gap-2">
        <span class="inline-flex items-center gap-2 rounded-full border px-3 py-1 text-xs {{ $bot->is_active ? 'border-emerald-500/40 bg-emerald-500/10 text-emerald-300' : 'border-slate-600 bg-slate-800 text-slate-400' }}">
            <span class="h-2 w-2 rounded-full {{ $bot->is_active ? 'bg-emerald-400' : 'bg-slate-500' }}"></span>
            {{ $bot->is_active ? 'Активен' : 'Выключен' }}
        </span>
        <span class="inline-flex items-center gap-2 rounded-full border px-3 py-1 text-xs {{ $tokenSet ? 'border-sky-500/40 bg-sky-500/10 text-sky-300' : 'border-amber-500/40 bg-amber-500/10 text-amber-300' }}">
            {{ $tokenSet ? 'Токен задан ···'.$tokenTail : 'Токен не задан' }}
        </span>
        <span class="inline-flex items-center rounded-full border border-slate-700 bg-slate-800 px-3 py-1 text-xs text-slate-400">
            {{ $bot->mode === 'webhook' ? 'Webhook' : 'Polling' }}
        </span>
    </div>
</div>

<div class="grid gap-6 xl:grid-cols-5">
    {{-- Левая колонка: подключение --}}
    <div class="space-y-6 xl:col-span-2">
        <form method="POST" action="{{ route('admin.client.bot.update') }}" class="rounded-xl border border-sky-500/20 bg-slate-900 p-5">
            @csrf
            @method('PUT')

            <div class="mb-5 flex items-center justify-between gap-3">
                <h2 class="font-semibold">Подключение Telegram</h2>
                <span class="text-xs text-slate-500">отдельно от бота прогрева</span>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Название</label>
                    <input type="text" name="name" value="{{ old('name', $bot->name) }}" required
                           class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-white">
                </div>

                <div>
                    <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Bot Token</label>
                    <input type="password" name="telegram_token" value="{{ old('telegram_token') }}"
                           placeholder="{{ $tokenSet ? '••••••••'.$tokenTail.' — новый токен для замены' : '123456789:AAH...' }}"
                           class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 font-mono text-sm text-white"
                           autocomplete="off">
                    <p class="mt-1 text-xs text-slate-500">@BotFather → вставьте сюда → сохраните</p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Режим</label>
                        <select name="mode" class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-white">
                            <option value="polling" @selected($bot->mode === 'polling')>Polling (dev)</option>
                            <option value="webhook" @selected($bot->mode === 'webhook')>Webhook (prod)</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Webhook secret</label>
                        <input type="text" name="webhook_secret" value="{{ old('webhook_secret', $bot->webhook_secret) }}"
                               class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 font-mono text-xs text-white">
                    </div>
                </div>

                @if ($username)
                <div class="rounded-lg border border-slate-800 bg-slate-800/40 px-3 py-2">
                    <div class="text-xs text-slate-500">Username</div>
                    <div class="font-mono text-sm text-sky-300">{{ '@'.$username }}</div>
                </div>
                @endif

                <label class="flex items-center gap-2 rounded-lg border border-slate-800 px-3 py-2">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $bot->is_active))
                           class="rounded border-slate-600 bg-slate-800 text-sky-500">
                    <span class="text-sm">Бот активен</span>
                </label>
            </div>

            <button type="submit" class="mt-5 w-full rounded-lg bg-sky-600 py-2.5 text-sm font-semibold text-white hover:bg-sky-500">
                Сохранить подключение
            </button>
        </form>

        <div class="rounded-xl border border-slate-800 bg-slate-900 p-5">
            <h2 class="mb-3 font-semibold">Сервис <code class="text-sm text-sky-300">client-bot/</code></h2>
            <p class="text-xs leading-relaxed text-slate-500">
                Запуск транспорта Telegram ↔ Laravel API. Токен и UUID — из этой панели и таблицы <code class="text-slate-400">bots</code> (<code class="text-slate-400">type=client</code>).
            </p>
            <dl class="mt-4 space-y-2 text-xs">
                <div class="flex justify-between gap-4 rounded-lg bg-slate-800/50 px-3 py-2">
                    <dt class="text-slate-500">BOT_UUID</dt>
                    <dd class="font-mono text-slate-300">{{ $bot->uuid }}</dd>
                </div>
                <div class="flex justify-between gap-4 rounded-lg bg-slate-800/50 px-3 py-2">
                    <dt class="text-slate-500">HMAC secret</dt>
                    <dd class="font-mono text-slate-300">{{ config('pikhlak.bot_hmac_secret') ? 'из api/.env' : '—' }}</dd>
                </div>
            </dl>
            <pre class="mt-4 overflow-x-auto rounded-lg border border-slate-800 bg-slate-950 p-3 text-[11px] leading-relaxed text-slate-400">cd client-bot
cp .env.example .env
# BOT_UUID — {{ $bot->uuid }}
# Токен Telegram — только в этой форме, client-bot подтянет из БД
npm install && npm run dev</pre>
        </div>
    </div>

    {{-- Правая колонка: планировщик + CRM --}}
    <div class="space-y-6 xl:col-span-3">
        <form method="POST" action="{{ route('admin.client.bot.notifications.update') }}" class="rounded-xl border border-slate-800 bg-slate-900 p-5">
            @csrf
            @method('PUT')

            <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="font-semibold">Расписание уведомлений</h2>
                    <p class="mt-1 text-xs text-slate-500">
                        Scheduler каждые {{ $schedulerMin }} мин:
                        <code class="text-sky-300">pikhlak:crm-sync</code>,
                        <code class="text-sky-300">pikhlak:client-bot-notify</code>
                    </p>
                </div>
                <button type="submit" class="shrink-0 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500">
                    Сохранить
                </button>
            </div>

            <div class="mb-3 hidden grid-cols-12 gap-3 px-1 text-[10px] font-medium uppercase tracking-wide text-slate-500 md:grid">
                <div class="col-span-4">Событие</div>
                <div class="col-span-6">Дни (минус — заранее, плюс — просрочка)</div>
                <div class="col-span-2 text-center">Вкл</div>
            </div>

            <div class="space-y-2">
                @foreach ($eventTypes as $type => $label)
                    @php $rule = $notificationRules->firstWhere('event_type', $type); @endphp
                    <div class="grid gap-3 rounded-lg border border-slate-800 p-3 md:grid-cols-12 md:items-center">
                        <div class="md:col-span-4">
                            <div class="text-sm font-medium">{{ $label }}</div>
                            <div class="mt-0.5 text-[11px] text-slate-500 md:hidden">-5 за 5 дн. · 0 в день · +3 просрочка</div>
                        </div>
                        <div class="md:col-span-6">
                            <input type="text" name="rules[{{ $type }}][offset_days]"
                                   value="{{ old('rules.'.$type.'.offset_days', $rule ? implode(', ', $rule->offset_days) : '-5, -3, -1, 0, 1, 3') }}"
                                   class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 font-mono text-sm text-white"
                                   placeholder="-5, -3, -1, 0, 1, 3">
                        </div>
                        <div class="flex justify-start md:col-span-2 md:justify-center">
                            <label class="inline-flex items-center gap-2 text-sm">
                                <input type="checkbox" name="rules[{{ $type }}][is_active]" value="1" @checked($rule?->is_active)
                                       class="rounded border-slate-600 bg-slate-800 text-emerald-500">
                                <span class="md:hidden">Активно</span>
                            </label>
                        </div>
                    </div>
                @endforeach
            </div>

            <p class="mt-4 text-xs text-slate-500">
                Пример: <code class="text-slate-400">-5, -3, -1, 0, 1, 3</code> — напоминания за 5/3/1 день, в день оплаты, через 1 и 3 дня просрочки.
            </p>
        </form>

        <div class="grid gap-4 md:grid-cols-2">
            <div class="rounded-xl border border-slate-800 bg-slate-900/60 p-5">
                <div class="mb-3 flex items-center justify-between gap-3">
                    <h3 class="text-sm font-semibold text-slate-200">Запросы менеджера</h3>
                    <a href="{{ route('admin.client.bot.manager-requests') }}" class="text-xs text-sky-400 hover:underline">Все заявки →</a>
                </div>
                @if ($pendingManagerRequests > 0)
                <p class="mb-3 text-sm text-amber-300">Ожидают обработки: <b>{{ $pendingManagerRequests }}</b></p>
                @else
                <p class="mb-3 text-sm text-slate-500">Новых заявок нет</p>
                @endif
                <ul class="space-y-2 text-xs">
                    @forelse ($latestManagerRequests as $req)
                    <li class="flex items-center justify-between gap-2 rounded-lg border border-slate-800 px-3 py-2">
                        <span class="text-slate-300">{{ $req->client?->full_name ?? 'Клиент' }}</span>
                        <span class="text-slate-500">{{ $req->created_at?->diffForHumans() }}</span>
                    </li>
                    @empty
                    <li class="text-slate-500">Заявок пока не было</li>
                    @endforelse
                </ul>
            </div>
            <div class="rounded-xl border border-slate-800 bg-slate-900/60 p-5">
                <h3 class="text-sm font-semibold text-slate-200">Что отправляет бот</h3>
                <ul class="mt-3 space-y-2 text-xs text-slate-400">
                    <li class="flex gap-2"><span class="text-sky-400">→</span> Текст напоминания (платёж / ТО / страховка)</li>
                    <li class="flex gap-2"><span class="text-sky-400">→</span> PDF-счёт и QR-код оплаты</li>
                    <li class="flex gap-2"><span class="text-sky-400">→</span> Ответы: остаток, дата ТО, следующий платёж</li>
                </ul>
            </div>
            <div class="rounded-xl border border-slate-800 bg-slate-900/60 p-5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h3 class="text-sm font-semibold text-slate-200">CRM → локальная БД</h3>
                        <p class="mt-1 text-xs text-slate-500">
                            Внешняя CRM — только источник. Бот и админка читают <code class="text-slate-400">rental_*</code> таблицы.
                        </p>
                    </div>
                    @if ($crmConfigured)
                    <form method="POST" action="{{ route('admin.client.crm.sync') }}">
                        @csrf
                        <button type="submit" class="rounded-lg bg-violet-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-violet-500">
                            Синхронизировать сейчас
                        </button>
                    </form>
                    @endif
                </div>

                @if ($lastCrmSync)
                <div class="mt-3 rounded-lg border border-slate-800 bg-slate-950/50 px-3 py-2 text-xs text-slate-400">
                    Последний sync: <span class="text-slate-300">{{ $lastCrmSync->finished_at?->format('d.m.Y H:i') ?? '—' }}</span>
                    · {{ $lastCrmSync->clients_synced }} ок
                    @if ($lastCrmSync->clients_failed > 0)
                        · <span class="text-amber-400">{{ $lastCrmSync->clients_failed }} ошибок</span>
                    @endif
                </div>
                @endif

                <p class="mt-3 text-xs text-slate-500">Поля из CRM:</p>
                <p class="mt-1 font-mono text-[10px] leading-relaxed text-slate-500">{{ implode(', ', $crmFields) }}</p>

                @unless ($crmConfigured)
                <p class="mt-3 text-xs text-amber-400/90">Сейчас demo: данные вводятся вручную в «База клиентов» или через seeder.</p>
                <ul class="mt-2 space-y-1 font-mono text-[11px] text-slate-400">
                    <li>CLIENT_BOT_CRM_DEMO=false</li>
                    <li>CLIENT_BOT_CRM_URL=...</li>
                    <li>CLIENT_BOT_CRM_TOKEN=...</li>
                </ul>
                @endunless
            </div>
        </div>
    </div>
</div>
@endsection
