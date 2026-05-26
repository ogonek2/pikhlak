@extends('admin.layouts.app')
@section('title', 'Модели ИИ')
@section('content')
<div class="mb-6"><a href="{{ route('admin.ai.index') }}" class="text-sm text-slate-500 hover:text-emerald-400">← ИИ центр</a></div>
<h1 class="mb-2 text-2xl font-bold">Модели ИИ</h1>
<p class="mb-6 text-slate-500">Добавляйте провайдеры без правки кода. API-ключи — в файле <code class="text-emerald-400">.env</code> (GROQ_API_KEY, GEMINI_API_KEY, OPENAI_API_KEY).</p>

<div class="mb-8 rounded-xl border border-slate-800 bg-slate-900 p-6">
    <h2 class="mb-4 font-semibold">Новая модель</h2>
    <form method="POST" action="{{ route('admin.ai.models.store') }}" class="grid gap-4 sm:grid-cols-2">
        @csrf
        <div>
            <label class="text-sm text-slate-400">Провайдер</label>
            <select name="provider" class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-white">
                @foreach ($providerLabels as $key => $label)
                <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-sm text-slate-400">Имя модели (API)</label>
            <input name="model_name" placeholder="llama-3.3-70b-versatile" required class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-white">
        </div>
        <div>
            <label class="text-sm text-slate-400">Подпись в админке</label>
            <input name="label" placeholder="Groq — быстрый ответ" class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-white">
        </div>
        <label class="flex items-end gap-2 pb-2">
            <input type="checkbox" name="is_active" value="1" checked class="text-emerald-500"> Активна
        </label>
        <div class="sm:col-span-2">
            <button type="submit" class="rounded-lg bg-emerald-600 px-4 py-2 text-white">Добавить</button>
        </div>
    </form>
</div>

<div class="space-y-3">
    @foreach ($models as $model)
    <form method="POST" action="{{ route('admin.ai.models.update', $model) }}" class="rounded-xl border border-slate-800 bg-slate-900 p-4">
        @csrf @method('PUT')
        <div class="grid gap-3 sm:grid-cols-4">
            <select name="provider" class="rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-white">
                @foreach (['groq','gemini','openai'] as $p)
                <option value="{{ $p }}" @selected($model->provider === $p)>{{ $providerLabels[$p] ?? $p }}</option>
                @endforeach
            </select>
            <input name="model_name" value="{{ $model->model_name }}" class="rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-white">
            <input name="label" value="{{ $model->config['label'] ?? $model->model_name }}" class="rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-white">
            <div class="flex items-center justify-between gap-2">
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="is_active" value="1" @checked($model->is_active) class="text-emerald-500"> Вкл
                </label>
                <button type="submit" class="text-sm text-emerald-400 hover:underline">Сохранить</button>
            </div>
        </div>
    </form>
    @endforeach
</div>
@endsection
