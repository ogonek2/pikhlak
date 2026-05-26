@extends('admin.layouts.app')
@section('title', 'ИИ центр')
@section('content')
<div class="mb-8">
    <h1 class="text-2xl font-bold">Искусственный интеллект</h1>
    <p class="text-slate-500">Простое управление: модели, маршруты, поведение бота</p>
</div>

<div class="mb-6 flex flex-wrap gap-2">
    @foreach ([
        'groq' => $providers['groq'],
        'gemini' => $providers['gemini'],
    ] as $name => $ok)
    <span class="rounded-full px-3 py-1 text-xs {{ $ok ? 'bg-emerald-500/20 text-emerald-400' : 'bg-slate-700 text-slate-500' }}">
        {{ strtoupper($name) }} {{ $ok ? '✓' : '— ключ в .env' }}
    </span>
    @endforeach
    <span class="rounded-full bg-slate-800 px-3 py-1 text-xs text-slate-400">Профиль: {{ $profile->is_active ? 'вкл' : 'выкл' }}</span>
</div>

<div class="mb-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
    <a href="{{ route('admin.ai.models') }}" class="rounded-xl border-2 border-emerald-500/40 bg-slate-900 p-5 hover:bg-slate-800">
        <h3 class="text-lg font-semibold text-emerald-400">1. Модели ИИ</h3>
        <p class="mt-1 text-sm text-slate-400">Groq, Gemini, OpenAI — добавить без кода</p>
    </a>
    <a href="{{ route('admin.ai.routes') }}" class="rounded-xl border-2 border-emerald-500/40 bg-slate-900 p-5 hover:bg-slate-800">
        <h3 class="text-lg font-semibold text-emerald-400">2. Маршруты</h3>
        <p class="mt-1 text-sm text-slate-400">Темы вопросов: каталог, лизинг, о компании…</p>
    </a>
    <a href="{{ route('admin.ai.rules') }}" class="rounded-xl border-2 border-violet-500/40 bg-slate-900 p-5 hover:bg-slate-800">
        <h3 class="text-lg font-semibold text-violet-400">3. Правила ИИ</h3>
        <p class="mt-1 text-sm text-slate-400">Факты, запреты, «не лизинг» и др.</p>
    </a>
    <a href="{{ route('admin.ai.filters') }}" class="rounded-xl border border-slate-800 bg-slate-900 p-5 hover:border-emerald-500/50">
        <h3 class="font-semibold">Поведение</h3>
        <p class="mt-1 text-sm text-slate-500">Автоответ, FAQ, базы данных, прогрев</p>
    </a>
    <a href="{{ route('admin.ai.settings') }}" class="rounded-xl border border-slate-800 bg-slate-900 p-5 hover:border-emerald-500/50">
        <h3 class="font-semibold">Личность бота</h3>
        <p class="mt-1 text-sm text-slate-500">Тон, роль, температура</p>
    </a>
    <a href="{{ route('admin.cars.index') }}" class="rounded-xl border border-slate-800 bg-slate-900 p-5 hover:border-emerald-500/50">
        <h3 class="font-semibold">Каталог авто</h3>
        <p class="mt-1 text-sm text-slate-500">ИИ использует только ваши авто</p>
    </a>
    <a href="{{ route('admin.ai.playground') }}" class="rounded-xl border border-slate-800 bg-slate-900 p-5 hover:border-emerald-500/50">
        <h3 class="font-semibold">Тест чата</h3>
        <p class="mt-1 text-sm text-slate-500">Проверка с каталогом и FAQ</p>
    </a>
</div>

<details class="rounded-xl border border-slate-800 bg-slate-900">
    <summary class="cursor-pointer px-5 py-3 text-sm font-medium text-slate-400">Расширенные настройки (промпты, темы, шаблоны)</summary>
    <div class="grid gap-2 border-t border-slate-800 p-4 sm:grid-cols-2">
        @foreach ([
            ['admin.ai.prompts', 'Промпты'],
            ['admin.ai.rules', 'Правила'],
            ['admin.ai.topics', 'Темы'],
            ['admin.ai.templates', 'Шаблоны'],
            ['admin.ai.warming', 'Сценарий прогрева'],
            ['admin.faq.index', 'FAQ'],
        ] as [$route, $title])
        <a href="{{ route($route) }}" class="text-sm text-emerald-400 hover:underline">{{ $title }}</a>
        @endforeach
    </div>
</details>
@endsection
