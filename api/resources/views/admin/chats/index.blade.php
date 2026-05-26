@extends('admin.layouts.app')

@section('title', 'Чаты')

@section('content')
<div class="mb-8">
    <h1 class="text-2xl font-bold">Чаты Telegram</h1>
</div>

<div class="overflow-hidden rounded-xl border border-slate-800">
    <table class="w-full text-left text-sm">
        <thead class="bg-slate-900 text-slate-500">
        <tr>
            <th class="px-4 py-3">Пользователь</th>
            <th class="px-4 py-3">Chat ID</th>
            <th class="px-4 py-3">Активность</th>
            <th class="px-4 py-3"></th>
        </tr>
        </thead>
        <tbody class="divide-y divide-slate-800">
        @forelse ($chats as $chat)
            <tr class="bg-slate-900/30">
                <td class="px-4 py-3">
                    {{ $chat->telegramUser?->first_name }}
                    @if ($chat->telegramUser?->username)
                        <span class="text-slate-500">@{{ $chat->telegramUser->username }}</span>
                    @endif
                </td>
                <td class="px-4 py-3 font-mono text-xs">{{ $chat->telegram_chat_id }}</td>
                <td class="px-4 py-3 text-slate-500">{{ $chat->last_activity_at?->diffForHumans() ?? '—' }}</td>
                <td class="px-4 py-3">
                    <a href="{{ route('admin.chats.show', $chat) }}" class="text-emerald-400 hover:underline">Открыть</a>
                </td>
            </tr>
        @empty
            <tr><td colspan="4" class="px-4 py-8 text-center text-slate-500">Чатов пока нет — напишите боту в Telegram</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $chats->links() }}</div>
@endsection
