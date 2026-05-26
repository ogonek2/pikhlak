@extends('admin.layouts.app')
@section('title', 'AI Topics')
@section('content')
<div class="mb-6"><a href="{{ route('admin.ai.index') }}" class="text-sm text-slate-500 hover:text-emerald-400">← AI Control</a></div>
<h1 class="mb-6 text-2xl font-bold">Фильтры тем</h1>
<div class="grid gap-8 lg:grid-cols-2">
    <div class="rounded-xl border border-emerald-500/20 bg-slate-900 p-5">
        <h2 class="mb-4 text-emerald-400">Разрешённые темы</h2>
        <p class="mb-4 text-xs text-slate-500">Пустой список = все темы разрешены (кроме запрещённых)</p>
        @foreach ($allowed as $t)
        <div class="mb-2 flex justify-between rounded bg-slate-800 px-3 py-2 text-sm">
            <span>{{ $t->topic }} <span class="text-slate-600">{{ implode(',', $t->keywords ?? []) }}</span></span>
            <form method="POST" action="{{ route('admin.ai.topics.allowed.destroy', $t) }}">@csrf @method('DELETE')<button class="text-red-400">×</button></form>
        </div>
        @endforeach
        <form method="POST" action="{{ route('admin.ai.topics.allowed.store') }}" class="mt-4 space-y-2">@csrf
            <input name="topic" placeholder="Тема" class="w-full rounded border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-white">
            <input name="keywords" placeholder="ключевые слова через запятую" class="w-full rounded border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-white">
            <button class="rounded bg-emerald-600 px-3 py-1 text-sm text-white">Добавить</button>
        </form>
    </div>
    <div class="rounded-xl border border-red-500/20 bg-slate-900 p-5">
        <h2 class="mb-4 text-red-400">Запрещённые темы</h2>
        @foreach ($forbidden as $t)
        <div class="mb-2 flex justify-between rounded bg-slate-800 px-3 py-2 text-sm">
            <span>{{ $t->topic }} ({{ $t->action }})</span>
            <form method="POST" action="{{ route('admin.ai.topics.forbidden.destroy', $t) }}">@csrf @method('DELETE')<button class="text-red-400">×</button></form>
        </div>
        @endforeach
        <form method="POST" action="{{ route('admin.ai.topics.forbidden.store') }}" class="mt-4 space-y-2">@csrf
            <input name="topic" placeholder="Тема" class="w-full rounded border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-white">
            <input name="keywords" placeholder="ключевые слова" class="w-full rounded border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-white">
            <select name="action" class="w-full rounded border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-white">
                <option value="fallback">fallback</option>
                <option value="block">block</option>
                <option value="escalate">escalate</option>
            </select>
            <button class="rounded bg-red-600/80 px-3 py-1 text-sm text-white">Добавить</button>
        </form>
    </div>
</div>
@endsection
