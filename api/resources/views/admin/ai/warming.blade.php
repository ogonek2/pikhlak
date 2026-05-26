@extends('admin.layouts.app')
@section('title', 'Lead Warming')
@section('content')
<div class="mb-6"><a href="{{ route('admin.ai.index') }}" class="text-sm text-slate-500 hover:text-emerald-400">← AI Control</a></div>
<h1 class="mb-2 text-2xl font-bold">Сценарий прогрева лидов</h1>
<p class="mb-6 text-sm text-slate-500">AI подставляет этап по warming_score лида (0-100)</p>
@php
$defaultSteps = [
    ['name' => 'Знакомство', 'instruction' => 'Поприветствуй, спроси класс авто'],
    ['name' => 'Бюджет', 'instruction' => 'Уточни бюджет и срок'],
    ['name' => 'Детали', 'instruction' => 'Марка, год, комплектация'],
    ['name' => 'Контакт', 'instruction' => 'Предложи менеджера'],
];
$json = json_encode($scenario?->steps ?? $defaultSteps, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
@endphp
<form method="POST" action="{{ route('admin.ai.warming.update') }}" class="max-w-3xl rounded-xl border border-slate-800 bg-slate-900 p-6 space-y-4">
    @csrf @method('PUT')
    <input name="name" value="{{ $scenario?->name ?? 'Стандартный прогрев' }}" class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-white">
    <div>
        <label class="text-sm text-slate-400">Шаги (JSON array)</label>
        <textarea name="steps_json" rows="14" class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 font-mono text-sm text-white">{{ $json }}</textarea>
    </div>
    <label class="flex items-center gap-2"><input type="checkbox" name="is_active" value="1" @checked($scenario?->is_active ?? true)> Активен</label>
    <button type="submit" class="rounded-lg bg-emerald-600 px-6 py-2 text-white">Сохранить сценарий</button>
</form>
@endsection
