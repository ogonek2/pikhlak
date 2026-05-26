@extends('admin.layouts.app')
@section('title', 'Редактирование: '.$route->name)
@section('content')
<div class="mb-6 flex flex-wrap items-start justify-between gap-4">
    <div>
        <a href="{{ route('admin.ai.routes') }}" class="text-sm text-slate-500 hover:text-emerald-400">← Маршруты ИИ</a>
        <h1 class="mt-2 text-2xl font-bold text-white">{{ $route->name }}</h1>
        <p class="mt-1 font-mono text-sm text-slate-500">{{ $route->slug }} · приоритет {{ $route->priority }}</p>
    </div>
    @if ($route->slug === 'default')
    <span class="rounded-lg border border-slate-700 px-4 py-2 text-sm text-slate-500"
          title="Системный маршрут default нельзя удалить">
        Удаление недоступно
    </span>
    @else
    <form method="POST" action="{{ route('admin.ai.routes.destroy', $route) }}"
          onsubmit="return confirm('Удалить маршрут «{{ $route->name }}»?')">
        @csrf @method('DELETE')
        <button type="submit" class="rounded-lg border border-red-500/40 px-4 py-2 text-sm text-red-400 hover:bg-red-500/10">
            Удалить маршрут
        </button>
    </form>
    @endif
</div>

<div class="rounded-xl border border-slate-800 bg-slate-900 p-6">
    @include('admin.ai.routes._form', [
        'route' => $route,
        'action' => route('admin.ai.routes.update', $route),
        'method' => 'PUT',
        'submitLabel' => 'Сохранить изменения',
    ])
</div>
@endsection
