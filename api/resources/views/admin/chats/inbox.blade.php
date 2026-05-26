@extends('admin.layouts.app')

@section('title', 'Переписки')

@section('content')
@php
    $modeLabel = fn ($chat) => ($chat->state['reply_mode'] ?? 'ai') === 'human' ? 'Оператор' : 'ИИ';
@endphp

<div class="mb-4 flex flex-wrap items-center justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-white">Переписки с ботом</h1>
        <p class="text-sm text-slate-500">Все диалоги Telegram-лидов в одном окне</p>
    </div>
    <a href="{{ route('admin.chats.settings') }}" class="text-sm text-slate-400 hover:text-emerald-400">⚙ Настройки чатов</a>
    @if ($pendingOperators > 0)
        <a href="{{ route('admin.chats.index', ['filter' => 'operator']) }}"
           class="inline-flex items-center gap-2 rounded-lg border border-orange-500/40 bg-orange-500/10 px-4 py-2 text-sm font-medium text-orange-300 hover:bg-orange-500/20">
            <span class="relative flex h-2 w-2">
                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-orange-400 opacity-75"></span>
                <span class="relative inline-flex h-2 w-2 rounded-full bg-orange-500"></span>
            </span>
            {{ $pendingOperators }} {{ $pendingOperators === 1 ? 'запрос оператора' : 'запросов оператора' }}
        </a>
    @endif
</div>

<div class="flex h-[calc(100vh-12rem)] min-h-[520px] overflow-hidden rounded-xl border border-slate-800 bg-slate-900/50">
    {{-- Список чатов --}}
    <aside class="flex w-full max-w-xs flex-col border-r border-slate-800 bg-slate-900 lg:max-w-sm">
        <div class="border-b border-slate-800 p-3">
            <form method="GET" action="{{ route('admin.chats.index') }}" class="space-y-2">
                @if ($selectedChat)
                    <input type="hidden" name="chat" value="{{ $selectedChat->id }}">
                @endif
                <input type="search" name="q" value="{{ $search }}" placeholder="Имя или @username…"
                       class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-white placeholder-slate-500 focus:border-emerald-500 focus:outline-none">
                <div class="flex flex-wrap gap-1 text-xs">
                    <a href="{{ route('admin.chats.index', array_filter(['chat' => $selectedChat?->id])) }}"
                       class="rounded px-2 py-1 {{ $filter === '' ? 'bg-emerald-500/20 text-emerald-300' : 'text-slate-400 hover:bg-slate-800' }}">Все</a>
                    <a href="{{ route('admin.chats.index', array_filter(['filter' => 'operator', 'chat' => $selectedChat?->id])) }}"
                       class="rounded px-2 py-1 {{ $filter === 'operator' ? 'bg-orange-500/20 text-orange-300' : 'text-slate-400 hover:bg-slate-800' }}">Оператор</a>
                    <a href="{{ route('admin.chats.index', array_filter(['filter' => 'human', 'chat' => $selectedChat?->id])) }}"
                       class="rounded px-2 py-1 {{ $filter === 'human' ? 'bg-violet-500/20 text-violet-300' : 'text-slate-400 hover:bg-slate-800' }}">Ручной</a>
                </div>
            </form>
        </div>
        <div class="flex-1 overflow-y-auto">
            @forelse ($chats as $chat)
                @php
                    $user = $chat->telegramUser;
                    $name = trim(($user->first_name ?? '').' '.($user->last_name ?? '')) ?: ($user->username ? '@'.$user->username : 'Чат #'.$chat->id);
                    $needsOp = $chat->lead?->needsOperator();
                    $isActive = $selectedChat && $selectedChat->id === $chat->id;
                @endphp
                <a href="{{ route('admin.chats.index', array_filter(['chat' => $chat->id, 'filter' => $filter ?: null, 'q' => $search ?: null])) }}"
                   class="block border-b border-slate-800/80 px-4 py-3 transition {{ $isActive ? 'bg-emerald-500/10' : 'hover:bg-slate-800/60' }}">
                    <div class="flex items-start justify-between gap-2">
                        <span class="truncate font-medium text-white">{{ $name }}</span>
                        @if ($needsOp)
                            <span class="shrink-0 rounded bg-orange-500/20 px-1.5 py-0.5 text-[10px] font-semibold uppercase text-orange-300">!</span>
                        @endif
                    </div>
                    <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-slate-500">
                        <span class="{{ ($chat->state['reply_mode'] ?? 'ai') === 'human' ? 'text-violet-400' : 'text-slate-500' }}">{{ $modeLabel($chat) }}</span>
                        <span>·</span>
                        <span>{{ $chat->messages_max_created_at ? \Carbon\Carbon::parse($chat->messages_max_created_at)->diffForHumans() : '—' }}</span>
                    </div>
                </a>
            @empty
                <p class="px-4 py-8 text-center text-sm text-slate-500">Чатов пока нет</p>
            @endforelse
        </div>
        @if ($chats->hasPages())
            <div class="border-t border-slate-800 p-2 text-xs">{{ $chats->links() }}</div>
        @endif
    </aside>

    {{-- Окно переписки --}}
    <section class="flex min-w-0 flex-1 flex-col">
        @if ($selectedChat)
            @php
                $user = $selectedChat->telegramUser;
                $displayName = trim(($user->first_name ?? '').' '.($user->last_name ?? '')) ?: ($user->username ? '@'.$user->username : 'Чат #'.$selectedChat->id);
                $lead = $selectedChat->lead;
            @endphp
            <header class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-800 bg-slate-900 px-4 py-3">
                <div>
                    <h2 class="font-semibold text-white">{{ $displayName }}</h2>
                    <p class="text-xs text-slate-500">
                        Telegram ID {{ $selectedChat->telegram_chat_id }}
                        @if ($lead)
                            · <a href="{{ route('admin.leads.index') }}" class="text-emerald-400 hover:underline">лид</a>
                            @if ($lead->status) · {{ $lead->status->name }} @endif
                        @endif
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    @if ($lead?->needsOperator())
                        <form method="POST" action="{{ route('admin.chats.operator-ack', $selectedChat) }}">
                            @csrf
                            <button type="submit" class="rounded-lg border border-orange-500/40 bg-orange-500/10 px-3 py-1.5 text-xs font-medium text-orange-300 hover:bg-orange-500/20">
                                ✓ Запрос обработан
                            </button>
                        </form>
                    @endif
                    <div class="inline-flex rounded-lg border border-slate-700 p-0.5 text-sm">
                        <form method="POST" action="{{ route('admin.chats.mode', $selectedChat) }}">
                            @csrf
                            <input type="hidden" name="mode" value="ai">
                            <button type="submit"
                                    class="rounded-md px-3 py-1.5 {{ $replyMode === 'ai' ? 'bg-emerald-600 text-white' : 'text-slate-400 hover:text-white' }}">
                                ИИ
                            </button>
                        </form>
                        <form method="POST" action="{{ route('admin.chats.mode', $selectedChat) }}">
                            @csrf
                            <input type="hidden" name="mode" value="human">
                            <button type="submit"
                                    class="rounded-md px-3 py-1.5 {{ $replyMode === 'human' ? 'bg-violet-600 text-white' : 'text-slate-400 hover:text-white' }}">
                                Ответить
                            </button>
                        </form>
                    </div>
                </div>
            </header>

            @if ($lead?->needsOperator())
                <div class="border-b border-orange-500/30 bg-orange-500/10 px-4 py-2 text-sm text-orange-200">
                    📞 Клиент запросил связь с оператором
                    @if ($lead->operator_requested_at)
                        ({{ $lead->operator_requested_at->format('d.m.Y H:i') }})
                    @endif
                    @if (!$disableAiOnOperator && $replyMode === 'ai')
                        <span class="block text-xs text-orange-300/80 mt-1">ИИ не отключён автоматически (см. <a href="{{ route('admin.chats.settings') }}" class="underline">настройки чатов</a>)</span>
                    @endif
                </div>
            @endif

            @if ($replyMode === 'human')
                <div class="border-b border-violet-500/30 bg-violet-500/10 px-4 py-2 text-sm text-violet-200">
                    Режим «Ответить»: бот не отвечает автоматически — пишите клиенту из поля ниже.
                </div>
            @endif

            <div id="chat-scroll" class="flex-1 space-y-3 overflow-y-auto p-4">
                @forelse ($messages as $msg)
                    @php
                        $isIn = $msg->direction === 'inbound';
                        $sender = $msg->payload['sender'] ?? ($isIn ? 'user' : ($msg->ai_message_id ? 'ai' : 'bot'));
                        $senderLabels = ['user' => 'Клиент', 'ai' => 'ИИ', 'admin' => 'Оператор', 'bot' => 'Бот'];
                        $label = $senderLabels[$sender] ?? ($isIn ? 'Клиент' : 'Исходящее');
                    @endphp
                    <div class="flex {{ $isIn ? 'justify-start' : 'justify-end' }}">
                        <div class="max-w-[85%] rounded-2xl px-4 py-2.5 text-sm {{ $isIn ? 'rounded-bl-md bg-slate-800 text-slate-100' : ($sender === 'admin' ? 'rounded-br-md bg-violet-600 text-white' : 'rounded-br-md bg-emerald-700 text-white') }}">
                            <div class="mb-1 flex items-center gap-2 text-[10px] font-semibold uppercase tracking-wide opacity-80">
                                <span>{{ $label }}</span>
                                <span>{{ $msg->created_at?->format('d.m H:i') }}</span>
                            </div>
                            <div class="whitespace-pre-wrap break-words">{{ $msg->body ?: '—' }}</div>
                        </div>
                    </div>
                @empty
                    <p class="text-center text-slate-500">Сообщений пока нет</p>
                @endforelse
            </div>

            <footer class="border-t border-slate-800 bg-slate-900 p-4">
                @if ($replyMode === 'human')
                    <form method="POST" action="{{ route('admin.chats.message', $selectedChat) }}" class="flex gap-2">
                        @csrf
                        <textarea name="body" rows="2" required maxlength="4000" placeholder="Ответ клиенту в Telegram…"
                                  class="min-h-[44px] flex-1 resize-y rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-white placeholder-slate-500 focus:border-violet-500 focus:outline-none"></textarea>
                        <button type="submit" class="self-end rounded-lg bg-violet-600 px-4 py-2 text-sm font-medium text-white hover:bg-violet-500">
                            Отправить
                        </button>
                    </form>
                @else
                    <p class="text-center text-sm text-slate-500">
                        Переключитесь на <strong class="text-violet-300">«Ответить»</strong>, чтобы писать клиенту вручную. В режиме <strong class="text-emerald-400">ИИ</strong> отвечает ассистент.
                    </p>
                @endif
            </footer>
        @else
            <div class="flex flex-1 flex-col items-center justify-center text-slate-500">
                <svg class="mb-4 h-16 w-16 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                </svg>
                <p>Выберите чат слева, чтобы читать переписку</p>
            </div>
        @endif
    </section>
</div>

@if ($selectedChat)
<script>
    const el = document.getElementById('chat-scroll');
    if (el) el.scrollTop = el.scrollHeight;
</script>
@endif
@endsection
