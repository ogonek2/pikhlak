@extends('admin.layouts.app')
@section('title', 'Правила ИИ')

@section('content')
<div class="mb-6 flex flex-wrap items-start justify-between gap-4">
    <div>
        <a href="{{ route('admin.ai.index') }}" class="text-sm text-slate-500 hover:text-emerald-400">← ИИ центр</a>
        <h1 class="mt-2 text-2xl font-bold text-white">Правила для ИИ</h1>
        <p class="mt-1 text-sm text-slate-500">Факты о компании, запреты, исправления (например «мы не лизинг»)</p>
    </div>
    <a href="#create-rule"
       class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-emerald-900/30 hover:bg-emerald-500">
        + Создать своё правило
    </a>
</div>

<div id="create-rule" class="mb-8 scroll-mt-6 rounded-xl border-2 border-emerald-500/50 bg-slate-900 p-6">
    <h2 class="mb-1 text-lg font-semibold text-emerald-400">Создать новое правило</h2>
    <p class="mb-4 text-sm text-slate-500">Напишите текст для ИИ и выберите, всегда ли оно действует или только при определённых словах в чате.</p>
    @include('admin.ai._rule_form', [
        'rule' => new \App\Models\AiPromptRule(['priority' => 80, 'is_active' => true, 'type' => 'constraint']),
        'action' => route('admin.ai.rules.store'),
        'method' => 'POST',
        'ruleTypes' => $ruleTypes,
        'submitLabel' => 'Создать правило',
    ])
</div>

<details class="mb-8 rounded-xl border border-violet-500/30 bg-violet-500/5">
    <summary class="cursor-pointer px-5 py-3 text-sm font-medium text-violet-200">
        Или добавить из готового шаблона (пресет)
    </summary>
    <div class="border-t border-violet-500/20 px-5 py-4">
        <div class="flex flex-wrap gap-2">
            @foreach ($rulePresets as $key => $preset)
            <form method="POST" action="{{ route('admin.ai.rules.preset', $key) }}">
                @csrf
                <button type="submit" class="rounded-lg border border-violet-500/40 bg-slate-900 px-4 py-2 text-sm text-violet-200 hover:bg-violet-500/10">
                    + {{ $preset['name'] }}
                </button>
            </form>
            @endforeach
        </div>
    </div>
</details>

<h2 class="mb-4 text-lg font-semibold text-white">Ваши правила ({{ $rules->count() }})</h2>

@forelse ($rules as $rule)
<div class="mb-4 rounded-xl border border-slate-800 bg-slate-900 p-6">
    <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
        <div>
            <h3 class="font-semibold text-white">{{ $rule->name ?: 'Правило #'.$rule->id }}</h3>
            <p class="text-xs text-slate-500">
                {{ $ruleTypes[$rule->type] ?? $rule->type }}
                · приоритет {{ $rule->priority }}
                @if($rule->isAlwaysOn())
                    · <span class="text-emerald-400">всегда</span>
                @elseif($rule->triggerKeywords())
                    · при: {{ implode(', ', array_slice($rule->triggerKeywords(), 0, 5)) }}…
                @endif
            </p>
        </div>
        <form method="POST" action="{{ route('admin.ai.rules.destroy', $rule) }}" onsubmit="return confirm('Удалить?')">
            @csrf @method('DELETE')
            <button type="submit" class="text-sm text-red-400">Удалить</button>
        </form>
    </div>
    @include('admin.ai._rule_form', [
        'rule' => $rule,
        'action' => route('admin.ai.rules.update', $rule),
        'method' => 'PUT',
        'ruleTypes' => $ruleTypes,
        'submitLabel' => 'Сохранить изменения',
    ])
</div>
@empty
<p class="rounded-lg border border-dashed border-slate-700 p-6 text-center text-slate-500">
    Правил пока нет — создайте своё <a href="#create-rule" class="text-emerald-400 underline">выше</a> или выберите пресет.
</p>
@endforelse
@endsection
