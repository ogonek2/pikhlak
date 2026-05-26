<!DOCTYPE html>
<html lang="uk" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Вход — Pikhlak Admin</title>
    @vite(['resources/css/app.css'])
</head>
<body class="flex min-h-full items-center justify-center bg-slate-950 px-4">
<div class="w-full max-w-md rounded-2xl border border-slate-800 bg-slate-900 p-8 shadow-xl">
    <div class="mb-8 text-center">
        <h1 class="text-2xl font-bold text-emerald-400">Pikhlak</h1>
        <p class="mt-1 text-sm text-slate-500">Панель управления ботом</p>
    </div>
    <form method="POST" action="{{ route('admin.login') }}" class="space-y-5">
        @csrf
        <div>
            <label class="mb-1 block text-sm text-slate-400">Email</label>
            <input type="email" name="email" value="{{ old('email', 'admin@pikhlak.local') }}" required
                   class="w-full rounded-lg border border-slate-700 bg-slate-800 px-4 py-2.5 text-white focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
        </div>
        <div>
            <label class="mb-1 block text-sm text-slate-400">Пароль</label>
            <input type="password" name="password" required
                   class="w-full rounded-lg border border-slate-700 bg-slate-800 px-4 py-2.5 text-white focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
        </div>
        <label class="flex items-center gap-2 text-sm text-slate-400">
            <input type="checkbox" name="remember" class="rounded border-slate-600 bg-slate-800 text-emerald-500">
            Запомнить меня
        </label>
        <button type="submit"
                class="w-full rounded-lg bg-emerald-600 py-2.5 font-semibold text-white hover:bg-emerald-500">
            Войти
        </button>
    </form>
</div>
</body>
</html>
