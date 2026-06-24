@php
    $link = fn ($route, $label, $icon) => request()->routeIs($route)
        ? '<a href="'.route($route).'" class="flex items-center gap-3 rounded-lg bg-sky-500/10 px-3 py-2 font-medium text-sky-400">'.$icon.$label.'</a>'
        : '<a href="'.route($route).'" class="flex items-center gap-3 rounded-lg px-3 py-2 text-slate-400 hover:bg-slate-800 hover:text-white">'.$icon.$label.'</a>';
@endphp
{!! $link('admin.client.dashboard', 'Дашборд', '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/></svg>') !!}
<p class="px-3 pt-4 text-xs font-semibold uppercase tracking-wider text-slate-600">Клиентский бот</p>
{!! $link('admin.client.bot.show', 'Токен и настройки', '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>') !!}
{!! $link('admin.client.bot.manager-requests', 'Запросы менеджера', '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>') !!}
<p class="px-3 pt-4 text-xs font-semibold uppercase tracking-wider text-slate-600">Клиенты</p>
{!! $link('admin.client.clients.index', 'База клиентов', '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>') !!}
{!! $link('admin.client.clients.create', 'Добавить клиента', '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>') !!}
<p class="px-3 pt-4 text-xs font-semibold uppercase tracking-wider text-slate-600">Трафик</p>
{!! $link('admin.client.traffic.index', 'Каналы и API', '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>') !!}
