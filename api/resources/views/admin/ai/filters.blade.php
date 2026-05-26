@extends('admin.layouts.app')
@section('title', 'AI Filters')
@section('content')
<div class="mb-6"><a href="{{ route('admin.ai.index') }}" class="text-sm text-slate-500 hover:text-emerald-400">← AI Control</a></div>
<h1 class="mb-6 text-2xl font-bold">Поведение ИИ</h1>
<form method="POST" action="{{ route('admin.ai.filters.update') }}" class="max-w-xl space-y-4 rounded-xl border border-slate-800 bg-slate-900 p-6">
    @csrf @method('PUT')
    <label class="flex items-center gap-2"><input type="checkbox" name="auto_reply" value="1" @checked($behavior['auto_reply'] ?? true) class="text-emerald-500">
        <span><b>Авто-ответ</b> — бот отвечает через ИИ</span></label>
    <label class="flex items-center gap-2"><input type="checkbox" name="use_project_data" value="1" @checked($behavior['use_project_data'] ?? true) class="text-emerald-500">
        <span><b>Базы данных</b> — каталог авто, FAQ, CRM (не выдумывать)</span></label>
    <label class="flex items-center gap-2"><input type="checkbox" name="use_faq_first" value="1" @checked($behavior['use_faq_first'] ?? true) class="text-emerald-500">
        <span>Сначала точный ответ из FAQ</span></label>
    <label class="flex items-center gap-2"><input type="checkbox" name="enable_warming" value="1" @checked($behavior['enable_warming'] ?? true) class="text-emerald-500">
        <span>Прогрев лидов (сценарии)</span></label>
    <label class="flex items-center gap-2"><input type="checkbox" name="strict_allowed_topics" value="1" @checked($behavior['strict_allowed_topics'] ?? false) class="text-emerald-500">
        <span>Строгий whitelist тем (обычно выкл)</span></label>
    <label class="flex items-center gap-2"><input type="checkbox" name="disable_ai_on_operator_request" value="1" @checked($behavior['disable_ai_on_operator_request'] ?? true) class="text-emerald-500">
        <span><b>При запросе оператора</b> — автоматически выключать ИИ (режим «Ответить»)</span></label>
    <p class="text-xs text-slate-500">Подробнее: <a href="{{ route('admin.chats.settings') }}" class="text-emerald-400 hover:underline">Настройки чатов</a></p>
    <div>
        <label class="text-sm text-slate-400">Агрессивность прогрева</label>
        <select name="warming_aggressiveness" class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-white">
            @foreach (['low' => 'Мягкий', 'medium' => 'Средний', 'high' => 'Активный'] as $v => $l)
            <option value="{{ $v }}" @selected(($behavior['warming_aggressiveness'] ?? 'medium') === $v)>{{ $l }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="text-sm text-slate-400">Порог совпадения FAQ (0-1)</label>
        <input type="number" step="0.05" name="faq_match_threshold" value="{{ $behavior['faq_match_threshold'] ?? 0.45 }}" class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-white">
    </div>
    <div>
        <label class="text-sm text-slate-400">Сообщений в контексте</label>
        <input type="number" name="max_context_messages" value="{{ $behavior['max_context_messages'] ?? 12 }}" class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-white">
    </div>
    <div>
        <label class="text-sm text-slate-400">Hot lead threshold (%)</label>
        <input type="number" name="hot_lead_threshold" value="{{ $behavior['hot_lead_threshold'] ?? 70 }}" class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-white">
    </div>
    <button type="submit" class="rounded-lg bg-emerald-600 px-6 py-2 text-white">Сохранить</button>
</form>
@endsection
