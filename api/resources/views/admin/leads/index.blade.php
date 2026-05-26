@extends('admin.layouts.app')

@section('title', 'Лиды')

@section('content')
<div class="mb-8 flex flex-wrap items-center justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold">Лиды CRM</h1>
        <p class="text-sm text-slate-500">Статусы, прогрев и запросы оператора</p>
    </div>
    <div class="flex flex-wrap gap-2 text-sm">
        <a href="{{ route('admin.leads.index') }}"
           class="rounded-lg px-3 py-1.5 {{ !request('filter') ? 'bg-emerald-500/20 text-emerald-300' : 'border border-slate-700 text-slate-400 hover:bg-slate-800' }}">Все</a>
        <a href="{{ route('admin.leads.index', ['filter' => 'operator']) }}"
           class="rounded-lg px-3 py-1.5 {{ request('filter') === 'operator' ? 'bg-orange-500/20 text-orange-300' : 'border border-slate-700 text-slate-400 hover:bg-slate-800' }}">
            Ждут оператора
            @if ($pendingOperators > 0)
                <span class="ml-1 rounded-full bg-orange-500 px-1.5 text-xs text-white">{{ $pendingOperators }}</span>
            @endif
        </a>
    </div>
</div>

<div class="overflow-hidden rounded-xl border border-slate-800">
    <table class="w-full text-left text-sm">
        <thead class="bg-slate-900 text-slate-500">
        <tr>
            <th class="px-4 py-3">Клиент</th>
            <th class="px-4 py-3">Статус</th>
            <th class="px-4 py-3">Оператор</th>
            <th class="px-4 py-3">Warming</th>
            <th class="px-4 py-3">Источник / реф.</th>
            <th class="px-4 py-3">Дата</th>
            <th class="px-4 py-3"></th>
        </tr>
        </thead>
        <tbody class="divide-y divide-slate-800">
        @forelse ($leads as $lead)
            @php $tu = $lead->chat?->telegramUser; @endphp
            <tr class="bg-slate-900/30 {{ $lead->needsOperator() ? 'ring-1 ring-inset ring-orange-500/30' : '' }}">
                <td class="px-4 py-3">
                    @if ($tu)
                        <div class="font-medium text-white">{{ trim(($tu->first_name ?? '').' '.($tu->last_name ?? '')) ?: '—' }}</div>
                        @if ($tu->username)
                            <div class="text-xs text-slate-500">@{{ $tu->username }}</div>
                        @endif
                    @else
                        <span class="font-mono text-xs text-slate-500">{{ Str::limit($lead->uuid, 8) }}</span>
                    @endif
                </td>
                <td class="px-4 py-3">
                    @if ($lead->status)
                        <span class="rounded-full px-2 py-1 text-xs" style="background: {{ $lead->status->color }}22; color: {{ $lead->status->color }}">
                            {{ $lead->status->name }}
                        </span>
                    @else
                        —
                    @endif
                </td>
                <td class="px-4 py-3">
                    @if ($lead->needsOperator())
                        <span class="inline-flex items-center gap-1 rounded-full bg-orange-500/20 px-2 py-1 text-xs font-medium text-orange-300">
                            📞 Ждёт
                        </span>
                        <div class="mt-1 text-[10px] text-slate-500">{{ $lead->operator_requested_at?->format('d.m H:i') }}</div>
                    @elseif ($lead->operator_handled_at)
                        <span class="text-xs text-slate-500">Обработан {{ $lead->operator_handled_at->format('d.m H:i') }}</span>
                    @else
                        <span class="text-slate-600">—</span>
                    @endif
                </td>
                <td class="px-4 py-3">
                    <span class="rounded-full px-2 py-1 text-xs {{ $lead->warming_score >= 70 ? 'bg-orange-500/20 text-orange-300' : ($lead->warming_score >= 40 ? 'bg-amber-500/20 text-amber-300' : 'bg-slate-800 text-slate-400') }}">
                        {{ $lead->warming_score }}%
                    </span>
                </td>
                <td class="px-4 py-3 text-slate-400">
                    <div class="text-xs">{{ $lead->source ?? '—' }}</div>
                    @if ($lead->referralLink)
                        <a href="{{ route('admin.referrals.show', $lead->referralLink) }}" class="text-emerald-400 hover:underline">
                            {{ $lead->referralLink->name }}
                        </a>
                    @endif
                </td>
                <td class="px-4 py-3 text-slate-500">{{ $lead->created_at?->format('d.m.Y H:i') }}</td>
                <td class="px-4 py-3">
                    <div class="flex flex-col items-end gap-2">
                        @if ($lead->chat_id)
                            <a href="{{ route('admin.chats.index', ['chat' => $lead->chat_id]) }}" class="text-xs text-violet-400 hover:underline">Переписка →</a>
                        @endif
                        <form method="POST" action="{{ route('admin.leads.update', $lead) }}" class="flex items-center gap-2">
                            @csrf @method('PATCH')
                            <select name="status_id" class="rounded border border-slate-700 bg-slate-800 px-2 py-1 text-xs text-white">
                                @foreach ($statuses as $status)
                                    <option value="{{ $status->id }}" @selected($lead->status_id == $status->id)>{{ $status->name }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="text-xs text-emerald-400 hover:underline">OK</button>
                        </form>
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="px-4 py-8 text-center text-slate-500">Лидов пока нет</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $leads->links() }}</div>
@endsection
