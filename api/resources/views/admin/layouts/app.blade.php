<!DOCTYPE html>
<html lang="uk" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') — Pikhlak</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-slate-950 text-slate-100 antialiased">
@php
    $clientSection = request()->routeIs('admin.client.*');
@endphp
<div class="flex min-h-full">
    <aside class="hidden w-64 flex-shrink-0 border-r border-slate-800 bg-slate-900 lg:flex lg:flex-col">
        <div class="flex h-16 items-center gap-2 border-b border-slate-800 px-6">
            <span class="text-lg font-bold text-emerald-400">Pikhlak</span>
            <span class="text-xs text-slate-500">Admin</span>
        </div>

        <div class="border-b border-slate-800 p-3">
            <div class="grid grid-cols-2 gap-1 rounded-lg bg-slate-950 p-1 text-xs">
                <a href="{{ route('admin.dashboard') }}"
                   class="rounded-md px-2 py-2 text-center font-medium {{ !$clientSection ? 'bg-emerald-600 text-white' : 'text-slate-400 hover:text-white' }}">
                    Прогрев
                </a>
                <a href="{{ route('admin.client.dashboard') }}"
                   class="rounded-md px-2 py-2 text-center font-medium {{ $clientSection ? 'bg-sky-600 text-white' : 'text-slate-400 hover:text-white' }}">
                    Клиенты
                </a>
            </div>
        </div>

        <nav class="flex-1 space-y-1 overflow-y-auto p-4 text-sm">
            @if ($clientSection)
                @include('admin.layouts.partials.nav-client')
            @else
                @include('admin.layouts.partials.nav-warming')
            @endif
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
            <div class="flex gap-2 text-xs">
                <a href="{{ route('admin.dashboard') }}" class="{{ !$clientSection ? 'text-emerald-400' : 'text-slate-500' }}">Прогрев</a>
                <a href="{{ route('admin.client.dashboard') }}" class="{{ $clientSection ? 'text-sky-400' : 'text-slate-500' }}">Клиенты</a>
            </div>
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
@stack('scripts')
</body>
</html>
