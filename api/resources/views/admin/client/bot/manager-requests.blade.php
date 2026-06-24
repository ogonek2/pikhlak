@extends('admin.layouts.app')
@section('title', 'Запросы менеджера')
@section('content')
<div class="mb-6 flex flex-wrap items-start justify-between gap-4">
    <div>
        <a href="{{ route('admin.client.bot.show') }}" class="text-sm text-slate-500 hover:text-sky-400">← Клиентский бот</a>
        <h1 class="mt-2 text-2xl font-bold">Запросы менеджера</h1>
        <p class="mt-1 text-sm text-slate-500">Клиенты клиентского бота, которые просят связаться с менеджером</p>
    </div>
    @if ($pendingCount > 0)
    <span class="rounded-full border border-amber-500/40 bg-amber-500/10 px-4 py-2 text-sm font-medium text-amber-300">
        Ожидают: {{ $pendingCount }}
    </span>
    @endif
</div>

<div class="mb-6 grid gap-6 xl:grid-cols-3">
    <form method="POST" action="{{ route('admin.client.bot.manager-settings') }}" class="rounded-xl border border-slate-800 bg-slate-900 p-5 xl:col-span-1">
        @csrf
        @method('PUT')
        <h2 class="mb-4 font-semibold">Настройки ответа клиенту</h2>
        <div class="space-y-4">
            <div>
                <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Телефон менеджера</label>
                <input type="text" name="manager_phone" value="{{ old('manager_phone', $managerSettings['phone']) }}"
                       placeholder="+380..."
                       class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-white">
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Текст подтверждения</label>
                <textarea name="manager_confirm_message" rows="5"
                          placeholder="Запрос принят, {name}. Мы свяжемся с вами. Срочно: {phone}"
                          class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-white">{{ old('manager_confirm_message', $managerSettings['confirm_message']) }}</textarea>
                <p class="mt-1 text-xs text-slate-500">Плейсхолдеры: <code class="text-slate-400">{name}</code>, <code class="text-slate-400">{phone}</code></p>
            </div>
        </div>
        <button type="submit" class="mt-4 w-full rounded-lg bg-sky-600 py-2.5 text-sm font-semibold text-white hover:bg-sky-500">
            Сохранить настройки
        </button>
    </form>

    <div class="rounded-xl border border-slate-800 bg-slate-900/60 p-5 xl:col-span-2">
        <h2 class="mb-3 font-semibold">Как это работает</h2>
        <ul class="space-y-2 text-sm text-slate-400">
            <li>Клиент нажимает кнопку «Менеджер» или пишет «нужен менеджер» в клиентском боте.</li>
            <li>Заявка попадает сюда — переписка клиентского бота <b class="text-slate-300">не сохраняется</b> в разделе «Чаты» прогрева.</li>
            <li>Обработайте заявку и отметьте статус — клиент получил автоответ при создании запроса.</li>
        </ul>
    </div>
</div>

<div class="mb-4 flex flex-wrap gap-2">
    @foreach (['pending' => 'Ожидают', 'in_progress' => 'В работе', 'resolved' => 'Обработаны', 'cancelled' => 'Отменены', 'all' => 'Все'] as $key => $label)
    <a href="{{ route('admin.client.bot.manager-requests', ['status' => $key]) }}"
       class="rounded-lg px-3 py-1.5 text-sm {{ $statusFilter === $key ? 'bg-sky-600 text-white' : 'border border-slate-700 text-slate-400 hover:bg-slate-800' }}">
        {{ $label }}
    </a>
    @endforeach
</div>

<div class="overflow-hidden rounded-xl border border-slate-800">
    <table class="w-full text-left text-sm">
        <thead class="bg-slate-900 text-slate-500">
        <tr>
            <th class="px-4 py-3">Клиент</th>
            <th class="px-4 py-3">Источник</th>
            <th class="px-4 py-3">Сообщение</th>
            <th class="px-4 py-3">Статус</th>
            <th class="px-4 py-3">Дата</th>
            <th class="px-4 py-3"></th>
        </tr>
        </thead>
        <tbody class="divide-y divide-slate-800">
        @forelse ($requests as $item)
            @php $client = $item->client; @endphp
            <tr class="bg-slate-900/30">
                <td class="px-4 py-3">
                    <div class="font-medium text-white">{{ $client?->full_name ?? '—' }}</div>
                    @if ($client)
                    <a href="{{ route('admin.client.clients.show', $client) }}" class="text-xs text-sky-400 hover:underline">Карточка клиента</a>
                    @endif
                </td>
                <td class="px-4 py-3 text-slate-400">{{ $item->sourceLabel() }}</td>
                <td class="px-4 py-3 max-w-xs">
                    <div class="truncate text-slate-300" title="{{ $item->client_message }}">{{ $item->client_message ?: '—' }}</div>
                </td>
                <td class="px-4 py-3">
                    @php
                        $statusClass = match ($item->status) {
                            'pending' => 'bg-amber-500/20 text-amber-300',
                            'in_progress' => 'bg-sky-500/20 text-sky-300',
                            'resolved' => 'bg-emerald-500/20 text-emerald-300',
                            default => 'bg-slate-700 text-slate-400',
                        };
                    @endphp
                    <span class="rounded-full px-2 py-0.5 text-xs {{ $statusClass }}">{{ $item->statusLabel() }}</span>
                </td>
                <td class="px-4 py-3 text-xs text-slate-500 whitespace-nowrap">
                    {{ $item->created_at?->format('d.m.Y H:i') }}
                    @if ($item->handler)
                        <div class="mt-1">{{ $item->handler->name ?? 'Admin' }}</div>
                    @endif
                </td>
                <td class="px-4 py-3">
                    @if ($item->isPending())
                    <div class="flex flex-wrap gap-2">
                        @if ($item->status === 'pending')
                        <form method="POST" action="{{ route('admin.client.bot.manager-requests.in-progress', $item) }}">
                            @csrf
                            <button type="submit" class="rounded bg-sky-600/20 px-2 py-1 text-xs text-sky-300 hover:bg-sky-600/30">В работу</button>
                        </form>
                        @endif
                        <form method="POST" action="{{ route('admin.client.bot.manager-requests.resolve', $item) }}" class="inline">
                            @csrf
                            <button type="submit" class="rounded bg-emerald-600/20 px-2 py-1 text-xs text-emerald-300 hover:bg-emerald-600/30">Готово</button>
                        </form>
                        <form method="POST" action="{{ route('admin.client.bot.manager-requests.cancel', $item) }}" class="inline">
                            @csrf
                            <button type="submit" class="rounded bg-slate-700 px-2 py-1 text-xs text-slate-400 hover:bg-slate-600">Отмена</button>
                        </form>
                    </div>
                    @elseif ($item->admin_notes)
                    <span class="text-xs text-slate-500" title="{{ $item->admin_notes }}">Есть заметка</span>
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="px-4 py-10 text-center text-slate-500">Заявок пока нет</td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>

@if ($requests->hasPages())
<div class="mt-4">{{ $requests->links() }}</div>
@endif
@endsection
