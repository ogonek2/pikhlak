@extends('admin.layouts.app')
@section('title', 'Редактирование')
@section('content')
<div class="mb-6">
    <a href="{{ route('admin.client.clients.show', $client) }}" class="text-sm text-slate-500 hover:text-sky-400">← {{ $client->full_name }}</a>
    <h1 class="mt-2 text-2xl font-bold">Редактирование</h1>
</div>

<form method="POST" action="{{ route('admin.client.clients.update', $client) }}" class="max-w-2xl space-y-6 rounded-xl border border-slate-800 bg-slate-900 p-6">
    @csrf @method('PUT')
    <div>
        <label class="mb-1 block text-sm text-slate-400">ФИО</label>
        <input name="full_name" value="{{ old('full_name', $client->full_name) }}" required class="w-full rounded-lg border border-slate-700 bg-slate-800 px-4 py-2 text-white">
    </div>
    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label class="mb-1 block text-sm text-slate-400">Email</label>
            <input name="email" type="email" value="{{ old('email', $client->email) }}" class="w-full rounded-lg border border-slate-700 bg-slate-800 px-4 py-2 text-white">
        </div>
        <div>
            <label class="mb-1 block text-sm text-slate-400">Telegram chat ID</label>
            <input name="telegram_chat_id" value="{{ old('telegram_chat_id', $client->telegram_chat_id) }}" class="w-full rounded-lg border border-slate-700 bg-slate-800 px-4 py-2 text-white">
        </div>
    </div>
    <div>
        <label class="mb-1 block text-sm text-slate-400">Статус</label>
        <select name="status" class="w-full rounded-lg border border-slate-700 bg-slate-800 px-4 py-2 text-white">
            @foreach ($statuses as $key => $label)
            <option value="{{ $key }}" @selected(old('status', $client->status) === $key)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="mb-1 block text-sm text-slate-400">Заметки</label>
        <textarea name="notes" rows="4" class="w-full rounded-lg border border-slate-700 bg-slate-800 px-4 py-2 text-white">{{ old('notes', $client->notes) }}</textarea>
    </div>
    <button type="submit" class="rounded-lg bg-sky-600 px-6 py-2.5 font-semibold text-white hover:bg-sky-500">Сохранить</button>
</form>
@endsection
