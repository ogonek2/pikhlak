@extends('admin.layouts.app')
@section('title', 'Маршруты ИИ')
@section('content')
<div class="mb-6 flex flex-wrap items-start justify-between gap-4">
    <div>
        <a href="{{ route('admin.ai.index') }}" class="text-sm text-slate-500 hover:text-emerald-400">← ИИ центр</a>
        <h1 class="mt-2 text-2xl font-bold text-white">Маршруты ИИ</h1>
        <p class="mt-1 text-sm text-slate-500">
            Всего: <span class="text-slate-300">{{ $totalCount }}</span>
            @if($routes->total() !== $totalCount)
                · показано по фильтру: {{ $routes->total() }}
            @endif
        </p>
    </div>
    <a href="{{ route('admin.ai.routes.create') }}"
       class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-emerald-900/30 hover:bg-emerald-500">
        + Создать маршрут
    </a>
</div>

<form method="GET" class="mb-4 flex flex-wrap items-center gap-3">
    <input name="q" value="{{ $search }}" placeholder="Поиск: название, slug, ключевые слова…"
           class="min-w-[220px] flex-1 rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-white sm:max-w-md">
    <select name="active" class="rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-white">
        <option value="">Все</option>
        <option value="1" @selected($active === '1')>Только активные</option>
        <option value="0" @selected($active === '0')>Только выключенные</option>
    </select>
    <button type="submit" class="rounded-lg border border-slate-700 px-4 py-2 text-sm text-slate-300 hover:bg-slate-800">Найти</button>
    @if($search !== '' || ($active ?? '') !== '')
    <a href="{{ route('admin.ai.routes') }}" class="text-sm text-slate-500 hover:text-white">Сброс</a>
    @endif
</form>

<details class="mb-6 rounded-xl border border-violet-500/30 bg-violet-500/5">
    <summary class="cursor-pointer px-5 py-3 text-sm font-medium text-violet-200">
        Добавить из готового шаблона (пресет)
    </summary>
    <div class="border-t border-violet-500/20 px-5 py-4">
        <p class="mb-3 text-xs text-slate-500">Создаёт или обновляет маршрут по slug — после добавления откроется редактирование.</p>
        <div class="flex flex-wrap gap-2">
            @foreach ($routePresets as $key => $preset)
            <form method="POST" action="{{ route('admin.ai.routes.preset', $key) }}">
                @csrf
                <button type="submit" class="rounded-lg border border-violet-500/40 bg-slate-900 px-4 py-2 text-sm text-violet-200 hover:bg-violet-500/10">
                    + {{ $preset['name'] }}
                </button>
            </form>
            @endforeach
        </div>
    </div>
</details>

<div class="mb-4 rounded-xl border border-slate-800 bg-slate-900/50 p-4 text-sm text-slate-400">
    <strong class="text-slate-300">Как работает:</strong> при совпадении ключевых слов выбирается маршрут с наибольшим приоритетом.
    Также учитываются <a href="{{ route('admin.ai.rules') }}" class="text-emerald-400 hover:underline">правила ИИ</a>.
</div>

<div class="overflow-hidden rounded-xl border border-slate-800">
    <table class="w-full text-left text-sm">
        <thead class="bg-slate-900 text-slate-400">
            <tr>
                <th class="px-4 py-3 w-16">Приор.</th>
                <th class="px-4 py-3">Маршрут</th>
                <th class="hidden px-4 py-3 lg:table-cell">Ключевые слова</th>
                <th class="hidden px-4 py-3 md:table-cell">Модель / профиль</th>
                <th class="hidden px-4 py-3 sm:table-cell">Данные</th>
                <th class="px-4 py-3 w-20">Статус</th>
                <th class="px-4 py-3 text-right">Действия</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-800">
            @forelse ($routes as $route)
            @php
                $keywords = $route->intent_keywords ?? [];
                $kwPreview = implode(', ', array_slice($keywords, 0, 6));
                if (count($keywords) > 6) {
                    $kwPreview .= '…';
                }
            @endphp
            <tr class="bg-slate-900/50 hover:bg-slate-800/50">
                <td class="px-4 py-3 font-mono text-slate-400">{{ $route->priority }}</td>
                <td class="px-4 py-3">
                    <a href="{{ route('admin.ai.routes.edit', $route) }}" class="font-medium text-white hover:text-emerald-400">
                        {{ $route->name }}
                    </a>
                    <div class="mt-0.5 font-mono text-xs text-slate-500">{{ $route->slug }}</div>
                    @if($route->extra_instruction)
                    <div class="mt-1 line-clamp-1 text-xs text-slate-600 lg:hidden" title="{{ $route->extra_instruction }}">
                        {{ Str::limit($route->extra_instruction, 80) }}
                    </div>
                    @endif
                </td>
                <td class="hidden px-4 py-3 lg:table-cell">
                    @if($kwPreview)
                    <span class="text-slate-400" title="{{ implode(', ', $keywords) }}">{{ Str::limit($kwPreview, 72) }}</span>
                    @else
                    <span class="text-slate-600">—</span>
                    @endif
                </td>
                <td class="hidden px-4 py-3 text-xs text-slate-400 md:table-cell">
                    @if($route->model)
                        {{ $route->model->config['label'] ?? $route->model->model_name }}
                    @else
                        <span class="text-slate-600">модель по умолч.</span>
                    @endif
                    <br>
                    @if($route->profile)
                        {{ $route->profile->name }}
                    @else
                        <span class="text-slate-600">профиль по умолч.</span>
                    @endif
                </td>
                <td class="hidden px-4 py-3 sm:table-cell">
                    <div class="flex flex-wrap gap-1">
                        @foreach ($route->data_sources ?? [] as $src)
                        <span class="rounded bg-slate-800 px-1.5 py-0.5 text-xs text-slate-400">{{ $src }}</span>
                        @endforeach
                    </div>
                </td>
                <td class="px-4 py-3">
                    @if($route->is_active)
                    <span class="rounded-full bg-emerald-500/20 px-2 py-0.5 text-xs text-emerald-400">вкл</span>
                    @else
                    <span class="rounded-full bg-slate-700 px-2 py-0.5 text-xs text-slate-500">выкл</span>
                    @endif
                </td>
                <td class="px-4 py-3">
                    <div class="flex flex-col items-end gap-1 text-sm whitespace-nowrap">
                        <a href="{{ route('admin.ai.routes.edit', $route) }}" class="text-emerald-400 hover:text-emerald-300">Изменить</a>
                        @if ($route->slug === 'default')
                        <span class="text-xs text-slate-600 cursor-help" title="Системный маршрут default нельзя удалить">Удалить</span>
                        @else
                        <form method="POST" action="{{ route('admin.ai.routes.destroy', $route) }}"
                              onsubmit="return confirm('Удалить маршрут «{{ $route->name }}»?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-xs text-red-400 hover:text-red-300">Удалить</button>
                        </form>
                        @endif
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="px-4 py-10 text-center text-slate-500">
                    @if($search !== '' || ($active ?? '') !== '')
                        Ничего не найдено — <a href="{{ route('admin.ai.routes') }}" class="text-emerald-400 underline">сбросить фильтр</a>
                    @else
                        Маршрутов пока нет —
                        <a href="{{ route('admin.ai.routes.create') }}" class="text-emerald-400 underline">создать первый</a>
                    @endif
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if ($routes->hasPages())
<div class="mt-4">
    {{ $routes->links() }}
</div>
@endif
@endsection
