@extends('admin.layouts.app')
@section('title', 'AI Playground')
@section('content')
<div class="mb-6"><a href="{{ route('admin.ai.index') }}" class="text-sm text-slate-500 hover:text-emerald-400">← AI Control</a></div>
<h1 class="mb-6 text-2xl font-bold">AI Playground</h1>
<form method="POST" action="{{ route('admin.ai.playground.run') }}" class="max-w-2xl rounded-xl border border-slate-800 bg-slate-900 p-6">
    @csrf
    <textarea name="message" rows="4" required class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-white" placeholder="Сообщение клиента...">{{ old('message') }}</textarea>
    <button type="submit" class="mt-4 rounded-lg bg-emerald-600 px-6 py-2 text-white">Отправить в AI</button>
</form>
@if (session('playground_reply'))
<div class="mt-6 max-w-2xl rounded-xl border border-emerald-500/30 bg-emerald-500/5 p-6">
    <h3 class="mb-2 text-sm text-emerald-400">Ответ AI</h3>
    <div class="whitespace-pre-wrap text-sm text-slate-200">{{ session('playground_reply') }}</div>
</div>
@endif
@endsection
