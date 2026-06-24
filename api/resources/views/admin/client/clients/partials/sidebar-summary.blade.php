@php
    $v = $summary['vehicle'] ?? null;
    $c = $summary['contract'] ?? null;
    $np = $summary['nextPayment'] ?? null;
    $nm = $summary['nextMaintenance'] ?? null;
    $initials = mb_strtoupper(mb_substr($client->full_name, 0, 1));
@endphp
<aside class="hidden w-64 shrink-0 xl:block">
    <div class="sticky top-6 space-y-4">
        <div class="overflow-hidden rounded-2xl border border-slate-800/80 bg-gradient-to-b from-slate-900 to-slate-950">
            <div class="border-b border-slate-800/80 px-5 py-6 text-center">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-sky-500/15 text-xl font-semibold text-sky-300 ring-1 ring-sky-500/30">
                    {{ $initials }}
                </div>
                <h2 class="mt-3 text-base font-semibold leading-tight">{{ $client->full_name }}</h2>
                <p class="mt-1 text-xs text-slate-500">{{ $statuses[$client->status] ?? $client->status }}</p>
            </div>

            <dl class="space-y-3 px-5 py-4 text-sm">
                @if ($client->phones->firstWhere('is_primary', true) ?? $client->phones->first())
                    <div>
                        <dt class="text-[10px] font-medium uppercase tracking-wider text-slate-600">Телефон</dt>
                        <dd class="mt-0.5 font-medium">{{ ($client->phones->firstWhere('is_primary', true) ?? $client->phones->first())->phone }}</dd>
                    </div>
                @endif
                @if ($v)
                    <div>
                        <dt class="text-[10px] font-medium uppercase tracking-wider text-slate-600">Авто</dt>
                        <dd class="mt-0.5">{{ $v->title() }}</dd>
                        @if ($v->plate_number)<dd class="text-xs text-slate-500">{{ $v->plate_number }}</dd>@endif
                    </div>
                @endif
                @if ($c)
                    <div>
                        <dt class="text-[10px] font-medium uppercase tracking-wider text-slate-600">Договор</dt>
                        <dd class="mt-0.5 font-mono text-xs text-sky-400">{{ $c->contract_number ?: 'без номера' }}</dd>
                        <dd class="text-xs text-slate-400">{{ number_format((float) $c->monthly_amount, 0) }} {{ $currencySymbols[$c->currency] ?? $c->currency }}/{{ $c->period_weeks ?? 4 }} нед.</dd>
                    </div>
                @endif
            </dl>
        </div>

        <div class="rounded-2xl border border-slate-800/80 bg-slate-900/50 p-4">
            <h3 class="text-[10px] font-semibold uppercase tracking-wider text-slate-500">Ближайшее</h3>
            <div class="mt-3 space-y-3 text-sm">
                @if ($np)
                    <div class="rounded-lg bg-slate-950/60 px-3 py-2">
                        <div class="text-xs text-slate-500">Платёж</div>
                        <div class="font-medium {{ $np->status === 'overdue' ? 'text-red-400' : 'text-white' }}">
                            {{ number_format((float) $np->amount, 0) }} · {{ $np->due_date->format('d.m.Y') }}
                        </div>
                    </div>
                @else
                    <p class="text-xs text-slate-600">Нет ожидающих платежей</p>
                @endif
                @if ($nm)
                    <div class="rounded-lg bg-slate-950/60 px-3 py-2">
                        <div class="text-xs text-slate-500">ТО / сервис</div>
                        <div class="font-medium">{{ $nm->title }}</div>
                        <div class="text-xs text-slate-400">{{ $nm->scheduled_at?->format('d.m.Y') }}</div>
                    </div>
                @endif
            </div>
        </div>

        <div class="rounded-2xl border border-slate-800/80 bg-slate-900/50 p-4 text-xs">
            <h3 class="text-[10px] font-semibold uppercase tracking-wider text-slate-500">Статус</h3>
            <ul class="mt-3 space-y-2">
                <li class="flex items-center justify-between">
                    <span class="text-slate-500">Telegram</span>
                    <span class="{{ $client->telegram_chat_id ? 'text-emerald-400' : 'text-amber-400' }}">
                        {{ $client->telegram_chat_id ? 'Привязан' : 'Нет' }}
                    </span>
                </li>
                <li class="flex items-center justify-between">
                    <span class="text-slate-500">Уведомления</span>
                    <span class="{{ $client->notifications_enabled ? 'text-sky-400' : 'text-slate-600' }}">
                        {{ $client->notifications_enabled ? 'Вкл' : 'Выкл' }}
                    </span>
                </li>
                @if ($summary['overduePayments'] > 0)
                <li class="flex items-center justify-between">
                    <span class="text-slate-500">Просрочка</span>
                    <span class="text-red-400">{{ $summary['overduePayments'] }}</span>
                </li>
                @endif
                @if ($client->crm_external_id)
                <li class="flex items-center justify-between">
                    <span class="text-slate-500">CRM</span>
                    <span class="font-mono text-violet-400">#{{ $client->crm_external_id }}</span>
                </li>
                @endif
            </ul>
            @if ($client->link_token && $botUsername)
            <div class="mt-4 border-t border-slate-800 pt-3">
                <div class="text-slate-600">Ссылка бота</div>
                <a href="https://t.me/{{ $botUsername }}?start=link_{{ $client->link_token }}" target="_blank" rel="noopener"
                   class="mt-1 block break-all text-sky-400 hover:underline">t.me/{{ $botUsername }}</a>
            </div>
            @endif
        </div>
    </div>
</aside>
