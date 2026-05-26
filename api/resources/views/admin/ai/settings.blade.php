@extends('admin.layouts.app')
@section('title', 'AI Профиль')
@section('content')
<div class="mb-6"><a href="{{ route('admin.ai.index') }}" class="text-sm text-slate-500 hover:text-emerald-400">← AI Control</a></div>
<h1 class="mb-6 text-2xl font-bold">Профиль AI</h1>
@php $p = $profile->personality ?? []; @endphp
<form method="POST" action="{{ route('admin.ai.settings.update') }}" class="max-w-3xl space-y-5 rounded-xl border border-slate-800 bg-slate-900 p-6">
    @csrf @method('PUT')
    <div class="grid gap-4 sm:grid-cols-2">
        <div><label class="text-sm text-slate-400">Название</label>
            <input name="name" value="{{ $profile->name }}" class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-white"></div>
        <div><label class="text-sm text-slate-400">Модель</label>
            <select name="model_id" class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-white">
                <option value="">— auto —</option>
                @foreach ($models as $m)
                    <option value="{{ $m->id }}" @selected($profile->model_id == $m->id)>{{ $m->provider }} / {{ $m->model_name }}</option>
                @endforeach
            </select></div>
        <div><label class="text-sm text-slate-400">Temperature</label>
            <input type="number" step="0.05" name="temperature" value="{{ $profile->temperature }}" class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-white"></div>
        <div><label class="text-sm text-slate-400">Max tokens</label>
            <input type="number" name="max_tokens" value="{{ $profile->max_tokens }}" class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-white"></div>
    </div>
    <div><label class="text-sm text-slate-400">Роль AI</label>
        <input name="role" value="{{ $p['role'] ?? '' }}" class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-white"></div>
    <div><label class="text-sm text-slate-400">Тон</label>
        <input name="tone" value="{{ $p['tone'] ?? '' }}" class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-white"></div>
    <div><label class="text-sm text-slate-400">О компании</label>
        <textarea name="company_info" rows="2" class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-white">{{ $p['company_info'] ?? '' }}</textarea></div>
    <div><label class="text-sm text-slate-400">Sales instructions (прогрев)</label>
        <textarea name="sales_instructions" rows="4" class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-white">{{ $p['sales_instructions'] ?? '' }}</textarea></div>
    <label class="flex items-center gap-2"><input type="checkbox" name="is_active" value="1" @checked($profile->is_active) class="text-emerald-500"> AI активен</label>
    <button type="submit" class="rounded-lg bg-emerald-600 px-6 py-2 font-semibold text-white hover:bg-emerald-500">Сохранить</button>
</form>
@endsection
