@extends('admin.layouts.app')

@section('title', $item->exists ? 'Редактировать FAQ' : 'Новый FAQ')

@section('content')
<div class="mb-8">
    <h1 class="text-2xl font-bold">{{ $item->exists ? 'Редактировать FAQ' : 'Новый FAQ' }}</h1>
</div>

<form method="POST" action="{{ $item->exists ? route('admin.faq.update', $item) : route('admin.faq.store') }}" class="max-w-2xl space-y-5 rounded-xl border border-slate-800 bg-slate-900 p-6">
    @csrf
    @if ($item->exists) @method('PUT') @endif

    <div>
        <label class="mb-1 block text-sm text-slate-400">Вопрос</label>
        <input type="text" name="question" value="{{ old('question', $item->question) }}" required
               class="w-full rounded-lg border border-slate-700 bg-slate-800 px-4 py-2 text-white">
    </div>
    <div>
        <label class="mb-1 block text-sm text-slate-400">Ответ</label>
        <textarea name="answer" rows="5" required class="w-full rounded-lg border border-slate-700 bg-slate-800 px-4 py-2 text-white">{{ old('answer', $item->answer) }}</textarea>
    </div>
    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="mb-1 block text-sm text-slate-400">Язык</label>
            <input type="text" name="locale" value="{{ old('locale', $item->locale) }}" class="w-full rounded-lg border border-slate-700 bg-slate-800 px-4 py-2 text-white">
        </div>
        <label class="flex items-end gap-2 pb-2">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $item->is_active))
                   class="rounded border-slate-600 text-emerald-500">
            <span class="text-sm">Активен</span>
        </label>
    </div>
    <div class="flex gap-3">
        <button type="submit" class="rounded-lg bg-emerald-600 px-6 py-2 font-semibold text-white hover:bg-emerald-500">Сохранить</button>
        <a href="{{ route('admin.faq.index') }}" class="rounded-lg border border-slate-700 px-6 py-2 text-slate-400 hover:bg-slate-800">Отмена</a>
    </div>
</form>
@endsection
