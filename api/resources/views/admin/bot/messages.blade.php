@extends('admin.layouts.app')

@section('title', 'Тексты бота')

@section('content')
@php
    $start = $messages['start'] ?? [];
    $callbacks = $messages['callbacks'] ?? [];
    $buttons = $start['buttons'] ?? [];
@endphp
<div class="mb-8">
    <h1 class="text-2xl font-bold">Тексты и кнопки бота</h1>
    <p class="text-slate-500">Изменения применяются сразу в Telegram. Поддерживается HTML: &lt;b&gt;, &lt;i&gt;, &lt;a&gt;</p>
</div>

<form method="POST" action="{{ route('admin.bot.messages.update') }}" class="space-y-8">
    @csrf
    @method('PUT')

    <section class="rounded-xl border border-slate-800 bg-slate-900 p-6">
        <h2 class="mb-4 text-lg font-semibold text-emerald-400">/start — приветствие</h2>
        <div>
            <label class="mb-1 block text-sm text-slate-400">Текст сообщения</label>
            <textarea name="start_text" rows="6" required
                      class="w-full rounded-lg border border-slate-700 bg-slate-800 px-4 py-3 font-mono text-sm text-white">{{ old('start_text', $start['text'] ?? '') }}</textarea>
        </div>
        <div class="mt-4 grid gap-4 sm:grid-cols-3">
            <div>
                <label class="mb-1 block text-xs text-slate-500">Кнопка «Каталог»</label>
                <input type="text" name="btn_cars" value="{{ old('btn_cars', $buttons[0]['label'] ?? '') }}" required
                       class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-white">
            </div>
            <div>
                <label class="mb-1 block text-xs text-slate-500">Кнопка «Калькулятор»</label>
                <input type="text" name="btn_calculator" value="{{ old('btn_calculator', $buttons[1]['label'] ?? '') }}" required
                       class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-white">
            </div>
            <div>
                <label class="mb-1 block text-xs text-slate-500">Кнопка «Менеджер»</label>
                <input type="text" name="btn_manager" value="{{ old('btn_manager', $buttons[2]['label'] ?? '') }}" required
                       class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-white">
            </div>
        </div>
    </section>

    <section class="rounded-xl border border-slate-800 bg-slate-900 p-6">
        <h2 class="mb-4 text-lg font-semibold text-emerald-400">Ответы на кнопки</h2>
        <div class="space-y-4">
            <div>
                <label class="mb-1 block text-sm text-slate-400">Каталог авто (cars)</label>
                <textarea name="callback_cars" rows="2" required class="w-full rounded-lg border border-slate-700 bg-slate-800 px-4 py-2 text-sm text-white">{{ old('callback_cars', $callbacks['cars'] ?? '') }}</textarea>
            </div>
            <div>
                <label class="mb-1 block text-sm text-slate-400">Калькулятор (calculator)</label>
                <textarea name="callback_calculator" rows="2" required class="w-full rounded-lg border border-slate-700 bg-slate-800 px-4 py-2 text-sm text-white">{{ old('callback_calculator', $callbacks['calculator'] ?? '') }}</textarea>
            </div>
            <div>
                <label class="mb-1 block text-sm text-slate-400">Менеджер (manager)</label>
                <textarea name="callback_manager" rows="2" required class="w-full rounded-lg border border-slate-700 bg-slate-800 px-4 py-2 text-sm text-white">{{ old('callback_manager', $callbacks['manager'] ?? '') }}</textarea>
            </div>
            <div>
                <label class="mb-1 block text-sm text-slate-400">Неизвестная кнопка (default)</label>
                <textarea name="callback_default" rows="2" required class="w-full rounded-lg border border-slate-700 bg-slate-800 px-4 py-2 text-sm text-white">{{ old('callback_default', $callbacks['default'] ?? '') }}</textarea>
            </div>
        </div>
    </section>

    <section class="rounded-xl border border-slate-800 bg-slate-900 p-6">
        <h2 class="mb-4 text-lg font-semibold text-emerald-400">Обычное сообщение</h2>
        <div>
            <label class="mb-1 block text-sm text-slate-400">Ответ на любой текст</label>
            <textarea name="default_reply" rows="3" required class="w-full rounded-lg border border-slate-700 bg-slate-800 px-4 py-2 text-sm text-white">{{ old('default_reply', $messages['default_reply'] ?? '') }}</textarea>
        </div>
        <div class="mt-4 w-32">
            <label class="mb-1 block text-sm text-slate-400">Typing (сек)</label>
            <input type="number" name="typing_duration" min="0" max="10" value="{{ old('typing_duration', $messages['typing_duration'] ?? 1) }}"
                   class="w-full rounded-lg border border-slate-700 bg-slate-800 px-4 py-2 text-white">
        </div>
    </section>

    <button type="submit" class="rounded-lg bg-emerald-600 px-8 py-3 font-semibold text-white hover:bg-emerald-500">
        Сохранить тексты
    </button>
</form>
@endsection
