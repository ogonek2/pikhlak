@extends('admin.layouts.app')
@section('title', 'Клиенты')
@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold">База клиентов</h1>
        <p class="text-slate-500">Аренда · авто · платежи · страховки · ТО</p>
    </div>
    <a href="{{ route('admin.client.clients.create') }}" class="rounded-lg bg-sky-600 px-4 py-2 text-sm font-medium text-white hover:bg-sky-500">+ Добавить</a>
</div>

<form method="GET" class="mb-4 flex flex-wrap gap-3">
    <input name="q" value="{{ $search }}" placeholder="Имя, телефон, номер авто…"
           class="rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-white">
    <select name="status" class="rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-white">
        <option value="">Все статусы</option>
        @foreach ($statuses as $key => $label)
        <option value="{{ $key }}" @selected($status === $key)>{{ $label }}</option>
        @endforeach
    </select>
    <button type="submit" class="rounded-lg border border-slate-700 px-4 py-2 text-sm text-slate-300 hover:bg-slate-800">Найти</button>
</form>

<div class="overflow-hidden rounded-xl border border-slate-800">
    <table class="w-full text-left text-sm">
        <thead class="bg-slate-900 text-slate-400">
            <tr>
                <th class="px-4 py-3">Клиент</th>
                <th class="px-4 py-3">Авто</th>
                <th class="px-4 py-3">Аренда</th>
                <th class="px-4 py-3">Статус</th>
                <th class="px-4 py-3 text-right">Действия</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-800">
            @forelse ($clients as $client)
            @php
                $vehicle = $client->vehicles->firstWhere('is_current', true) ?? $client->vehicles->first();
                $contract = $client->contracts->firstWhere('status', 'active') ?? $client->contracts->first();
            @endphp
            <tr class="bg-slate-900/50 hover:bg-slate-800/50">
                <td class="px-4 py-3">
                    <a href="{{ route('admin.client.clients.show', $client) }}" class="font-medium text-white hover:text-sky-400">{{ $client->full_name }}</a>
                    @if ($client->phones->first())
                    <div class="text-xs text-slate-500">{{ $client->phones->first()->phone }}</div>
                    @endif
                </td>
                <td class="px-4 py-3 text-slate-400">
                    @if ($vehicle)
                        {{ $vehicle->make }} {{ $vehicle->model }}
                        @if ($vehicle->plate_number)<span class="text-slate-600"> · {{ $vehicle->plate_number }}</span>@endif
                    @else — @endif
                </td>
                <td class="px-4 py-3 text-slate-400">
                    @if ($contract)
                        ${{ number_format($contract->monthly_amount, 0) }}/{{ $contract->period_weeks ?? 4 }}н
                        @if ($contract->rent_end)<br><span class="text-xs">до {{ $contract->rent_end->format('d.m.Y') }}</span>@endif
                    @else — @endif
                </td>
                <td class="px-4 py-3">
                    <span class="rounded-full bg-slate-800 px-2 py-0.5 text-xs">{{ $statuses[$client->status] ?? $client->status }}</span>
                </td>
                <td class="px-4 py-3 text-right">
                    <a href="{{ route('admin.client.clients.show', $client) }}" class="text-sky-400 hover:underline">Профиль</a>
                </td>
            </tr>
            @empty
            <tr><td colspan="5" class="px-4 py-10 text-center text-slate-500">Клиентов пока нет</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $clients->links() }}</div>
@endsection
