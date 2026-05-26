@extends('admin.layouts.app')
@section('title', 'Авто в наличии')
@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold">Авто в наличии</h1>
        <p class="text-slate-500">Полный каталог — в боте видны только <b>published</b></p>
    </div>
    <a href="{{ route('admin.cars.create') }}" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-500">+ Добавить</a>
</div>

<form method="GET" class="mb-4 flex flex-wrap gap-3">
    <input name="q" value="{{ $search ?? '' }}" placeholder="Поиск: марка, модель, VIN"
           class="rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-white">
    <select name="status" class="rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-white">
        <option value="">Все статусы</option>
        @foreach (['draft','published','reserved','sold','archived'] as $s)
        <option value="{{ $s }}" @selected(($status ?? '') === $s)>{{ $s }}</option>
        @endforeach
    </select>
    <button type="submit" class="rounded-lg border border-slate-700 px-4 py-2 text-sm text-slate-300 hover:bg-slate-800">Найти</button>
    @if(($search ?? '') || ($status ?? ''))
    <a href="{{ route('admin.cars.index') }}" class="px-2 py-2 text-sm text-slate-500 hover:text-white">Сброс</a>
    @endif
</form>

<div class="overflow-hidden rounded-xl border border-slate-800">
    <table class="w-full text-left text-sm">
        <thead class="bg-slate-900 text-slate-400">
            <tr>
                <th class="px-4 py-3">ID</th>
                <th class="px-4 py-3">Фото</th>
                <th class="px-4 py-3">Авто</th>
                <th class="px-4 py-3">Цена</th>
                <th class="px-4 py-3">Статус</th>
                <th class="px-4 py-3 text-right">Действия</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-800">
            @forelse ($cars as $car)
            <tr class="bg-slate-900/50 hover:bg-slate-800/50">
                <td class="px-4 py-3 text-slate-500">#{{ $car->id }}</td>
                <td class="px-4 py-3">
                    @if ($car->media->first())
                        <img src="{{ $car->media->first()->publicUrl() }}" alt="" class="h-12 w-16 rounded object-cover">
                    @else
                        <span class="text-xs text-slate-600">нет фото</span>
                    @endif
                </td>
                <td class="px-4 py-3">
                    <a href="{{ route('admin.cars.show', $car) }}" class="font-medium text-white hover:text-emerald-400">{{ $car->title() }}</a>
                    @if($car->vin)<div class="text-xs text-slate-500">VIN: {{ $car->vin }}</div>@endif
                </td>
                <td class="px-4 py-3">{{ $car->formattedPrice() }}</td>
                <td class="px-4 py-3">
                    <span class="rounded-full px-2 py-0.5 text-xs {{ $car->status === 'published' ? 'bg-emerald-500/20 text-emerald-400' : 'bg-slate-700 text-slate-400' }}">
                        {{ $car->status }}
                    </span>
                </td>
                <td class="px-4 py-3 text-right">
                    <div class="flex justify-end gap-2 text-xs">
                        <a href="{{ route('admin.cars.show', $car) }}" class="text-slate-400 hover:text-white">Просмотр</a>
                        <a href="{{ route('admin.cars.edit', $car) }}" class="text-emerald-400 hover:underline">Изменить</a>
                        <form method="POST" action="{{ route('admin.cars.duplicate', $car) }}" class="inline">@csrf
                            <button type="submit" class="text-slate-400 hover:text-white">Копия</button>
                        </form>
                        <form method="POST" action="{{ route('admin.cars.destroy', $car) }}" class="inline" onsubmit="return confirm('Удалить {{ $car->title() }}?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-red-400 hover:underline">Удалить</button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">Нет автомобилей</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $cars->links() }}</div>
@endsection
