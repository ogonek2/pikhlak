@extends('admin.layouts.app')
@section('title', 'AI Templates')
@section('content')
<div class="mb-6"><a href="{{ route('admin.ai.index') }}" class="text-sm text-slate-500 hover:text-emerald-400">← AI Control</a></div>
<h1 class="mb-6 text-2xl font-bold">Шаблоны ответов</h1>
<form method="POST" action="{{ route('admin.ai.templates.store') }}" class="mb-6 rounded-xl border border-slate-800 bg-slate-900 p-4 space-y-3">
    @csrf
    <div class="grid gap-3 sm:grid-cols-3">
        <input name="code" placeholder="code: fallback, blocked, api_error" class="rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-white">
        <input name="locale" value="uk" class="rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-white">
        <button class="rounded-lg bg-emerald-600 px-4 py-2 text-white">Сохранить</button>
    </div>
    <textarea name="template" rows="3" required class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-white" placeholder="Текст шаблона"></textarea>
</form>
@foreach ($templates as $t)
<div class="mb-3 rounded-lg border border-slate-800 bg-slate-900 p-4">
    <div class="font-mono text-sm text-emerald-400">{{ $t->code }} ({{ $t->locale }})</div>
    <p class="mt-2 text-sm text-slate-300">{{ $t->template }}</p>
</div>
@endforeach
@endsection
