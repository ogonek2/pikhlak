@extends('admin.layouts.app')
@section('title', 'AI Prompts')
@section('content')
<div class="mb-6"><a href="{{ route('admin.ai.index') }}" class="text-sm text-slate-500 hover:text-emerald-400">← AI Control</a></div>
<h1 class="mb-6 text-2xl font-bold">System Prompt</h1>
<div class="grid gap-8 lg:grid-cols-2">
    <form method="POST" action="{{ route('admin.ai.prompts.store') }}" class="rounded-xl border border-slate-800 bg-slate-900 p-6">
        @csrf
        <h2 class="mb-3 font-semibold text-emerald-400">Новая версия</h2>
        <textarea name="content" rows="16" required class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 font-mono text-sm text-white" placeholder="System prompt..."></textarea>
        <button type="submit" class="mt-4 rounded-lg bg-emerald-600 px-5 py-2 text-white">Создать v{{ ($prompts->max('version') ?? 0) + 1 }}</button>
    </form>
    <div class="space-y-3">
        @foreach ($prompts as $prompt)
        <div class="rounded-lg border {{ $prompt->is_published ? 'border-emerald-500/40 bg-emerald-500/5' : 'border-slate-700 bg-slate-900' }} p-4">
            <div class="mb-2 flex justify-between">
                <span class="font-mono text-sm">v{{ $prompt->version }}</span>
                @if ($prompt->is_published)
                    <span class="text-xs text-emerald-400">● Published</span>
                @else
                    <form method="POST" action="{{ route('admin.ai.prompts.publish', $prompt) }}">@csrf
                        <button class="text-xs text-emerald-400 hover:underline">Опубликовать</button>
                    </form>
                @endif
            </div>
            <pre class="max-h-40 overflow-auto text-xs text-slate-400 whitespace-pre-wrap">{{ $prompt->content }}</pre>
        </div>
        @endforeach
    </div>
</div>
@endsection
