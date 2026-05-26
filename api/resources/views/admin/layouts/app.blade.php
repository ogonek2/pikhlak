<!DOCTYPE html>
<html lang="uk" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Admin') — Pikhlak</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-slate-950 text-slate-100 antialiased">
<div class="flex min-h-full">
    <aside class="hidden w-64 flex-shrink-0 border-r border-slate-800 bg-slate-900 lg:flex lg:flex-col">
        <div class="flex h-16 items-center gap-2 border-b border-slate-800 px-6">
            <span class="text-lg font-bold text-emerald-400">Pikhlak</span>
            <span class="text-xs text-slate-500">Admin</span>
        </div>
        <nav class="flex-1 space-y-1 p-4 text-sm">
            @php
                $link = fn ($route, $label, $icon) => request()->routeIs($route)
                    ? '<a href="'.route($route).'" class="flex items-center gap-3 rounded-lg bg-emerald-500/10 px-3 py-2 font-medium text-emerald-400">'.$icon.$label.'</a>'
                    : '<a href="'.route($route).'" class="flex items-center gap-3 rounded-lg px-3 py-2 text-slate-400 hover:bg-slate-800 hover:text-white">'.$icon.$label.'</a>';
            @endphp
            {!! $link('admin.dashboard', 'Дашборд', '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/></svg>') !!}
            <p class="px-3 pt-4 text-xs font-semibold uppercase tracking-wider text-slate-600">Бот</p>
            {!! $link('admin.bot.show', 'Настройки бота', '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>') !!}
            {!! $link('admin.bot.messages', 'Тексты и кнопки', '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>') !!}
            <p class="px-3 pt-4 text-xs font-semibold uppercase tracking-wider text-slate-600">Каталог</p>
            {!! $link('admin.cars.index', 'Авто в наличии', '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>') !!}
            <p class="px-3 pt-4 text-xs font-semibold uppercase tracking-wider text-slate-600">ИИ</p>
            {!! $link('admin.ai.index', 'ИИ центр', '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>') !!}
            {!! $link('admin.ai.models', 'Модели ИИ', '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>') !!}
            {!! $link('admin.ai.routes', 'Маршруты ИИ', '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"/></svg>') !!}
            {!! $link('admin.ai.filters', 'Поведение ИИ', '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>') !!}
            {!! $link('admin.faq.index', 'FAQ', '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>') !!}
            <p class="px-3 pt-4 text-xs font-semibold uppercase tracking-wider text-slate-600">CRM</p>
            {!! $link('admin.chats.index', 'Чаты', '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z"/></svg>') !!}
            {!! $link('admin.referrals.index', 'Реф. ссылки', '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>') !!}
            {!! $link('admin.leads.index', 'Лиды', '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>') !!}
            {!! $link('admin.lead-statuses.index', 'Статусы лидов', '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>') !!}
        </nav>
        <div class="border-t border-slate-800 p-4 text-xs text-slate-500">
            @isset($currentProject)
                <div class="mb-2 font-medium text-slate-400">{{ $currentProject->name }}</div>
            @endisset
            <div>{{ auth()->user()->name }}</div>
            <form method="POST" action="{{ route('admin.logout') }}" class="mt-2">
                @csrf
                <button type="submit" class="text-red-400 hover:text-red-300">Выйти</button>
            </form>
        </div>
    </aside>

    <div class="flex flex-1 flex-col">
        <header class="flex h-16 items-center justify-between border-b border-slate-800 bg-slate-900/50 px-6 lg:hidden">
            <span class="font-bold text-emerald-400">Pikhlak Admin</span>
        </header>
        <main class="flex-1 p-6">
            @if (session('success'))
                <div class="mb-6 rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-emerald-300">
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="mb-6 rounded-lg border border-red-500/30 bg-red-500/10 px-4 py-3 text-red-300">
                    {{ session('error') }}
                </div>
            @endif
            @if ($errors->any())
                <div class="mb-6 rounded-lg border border-red-500/30 bg-red-500/10 px-4 py-3 text-red-300">
                    <ul class="list-inside list-disc text-sm">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            @yield('content')
        </main>
    </div>
</div>
</body>
</html>
