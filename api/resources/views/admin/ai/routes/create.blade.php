@extends('admin.layouts.app')
@section('title', 'Новый маршрут ИИ')
@section('content')
<div class="mb-6">
    <a href="{{ route('admin.ai.routes') }}" class="text-sm text-slate-500 hover:text-emerald-400">← Маршруты ИИ</a>
    <h1 class="mt-2 text-2xl font-bold text-white">Новый маршрут</h1>
    <p class="mt-1 text-sm text-slate-500">Slug — латиница, например <code class="text-emerald-400">catalog_prices</code></p>
</div>

@if (!empty($routePresets))
<details class="mb-6 rounded-xl border border-violet-500/30 bg-violet-500/5">
    <summary class="cursor-pointer px-5 py-3 text-sm font-medium text-violet-200">
        Или создать из шаблона
    </summary>
    <div class="border-t border-violet-500/20 px-5 py-4">
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
@endif

<div class="rounded-xl border border-slate-800 bg-slate-900 p-6">
    @include('admin.ai.routes._form', [
        'route' => $route,
        'action' => route('admin.ai.routes.store'),
        'method' => 'POST',
        'submitLabel' => 'Создать маршрут',
    ])
</div>
@endsection
