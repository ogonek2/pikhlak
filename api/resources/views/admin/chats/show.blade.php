@extends('admin.layouts.app')

@section('title', 'Чат')

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.chats.index') }}" class="text-sm text-slate-500 hover:text-emerald-400">← Назад к чатам</a>
    <h1 class="mt-2 text-2xl font-bold">
        {{ $chat->telegramUser?->first_name ?? 'Чат' }}
        @if ($chat->telegramUser?->username) <span class="text-slate-500">@{{ $chat->telegramUser->username }}</span> @endif
    </h1>
</div>

<div class="max-w-3xl space-y-3 rounded-xl border border-slate-800 bg-slate-900 p-6">
    @forelse ($chat->messages->reverse() as $message)
        <div class="flex {{ $message->direction === 'outbound' ? 'justify-end' : 'justify-start' }}">
            <div class="max-w-[80%] rounded-lg px-4 py-2 text-sm {{ $message->direction === 'outbound' ? 'bg-emerald-600/20 text-emerald-100' : 'bg-slate-800 text-slate-200' }}">
                <div class="mb-1 text-xs opacity-60">{{ $message->direction }} · {{ $message->created_at?->format('H:i d.m') }}</div>
                {{ $message->body }}
            </div>
        </div>
    @empty
        <p class="text-center text-slate-500">Сообщений в БД пока нет</p>
    @endforelse
</div>
@endsection
