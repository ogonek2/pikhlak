@extends('admin.layouts.app')

@section('title', 'Настройки чатов')

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.chats.index') }}" class="text-sm text-slate-500 hover:text-emerald-400">← Переписки</a>
    <h1 class="mt-2 text-2xl font-bold">Настройки чатов</h1>
    <p class="text-sm text-slate-500">Поведение бота при запросе оператора и ручном режиме</p>
</div>

<form method="POST" action="{{ route('admin.chats.settings.update') }}" class="max-w-xl space-y-6 rounded-xl border border-slate-800 bg-slate-900 p-6">
    @csrf
    @method('PUT')

    <fieldset class="space-y-4">
        <legend class="text-sm font-semibold text-white">Запрос оператора (кнопка «Менеджер» или фраза в чате)</legend>

        <label class="flex cursor-pointer gap-3 rounded-lg border border-slate-700 p-4 transition hover:bg-slate-800/50">
            <input type="radio" name="disable_ai_on_operator_request" value="1"
                   @checked($disableAiOnOperator) class="mt-1 text-emerald-500">
            <span>
                <span class="font-medium text-white">Автоматически выключать ИИ</span>
                <span class="mt-1 block text-sm text-slate-400">
                    Чат переключается в режим «Ответить». Ассистент не отвечает, пока вы снова не включите «ИИ» или не ответите вручную.
                </span>
            </span>
        </label>

        <label class="flex cursor-pointer gap-3 rounded-lg border border-slate-700 p-4 transition hover:bg-slate-800/50">
            <input type="radio" name="disable_ai_on_operator_request" value="0"
                   @checked(!$disableAiOnOperator) class="mt-1 text-emerald-500">
            <span>
                <span class="font-medium text-white">Не отключать ИИ</span>
                <span class="mt-1 block text-sm text-slate-400">
                    Лид и уведомление «ждёт оператора» создаются, но режим чата не меняется — ИИ может продолжать отвечать параллельно.
                </span>
            </span>
        </label>
    </fieldset>

    <p class="text-xs text-slate-500">
        Режим «ИИ / Ответить» для каждого чата по-прежнему переключается вручную в переписке.
        Дополнительные параметры ИИ — в разделе
        <a href="{{ route('admin.ai.filters') }}" class="text-emerald-400 hover:underline">Поведение ИИ</a>.
    </p>

    <button type="submit" class="rounded-lg bg-emerald-600 px-6 py-2.5 font-medium text-white hover:bg-emerald-500">
        Сохранить
    </button>
</form>
@endsection
