@extends('admin.layouts.app')

@section('title', 'FAQ')

@section('content')
<div class="mb-6 flex items-center justify-between">
    <h1 class="text-2xl font-bold">FAQ</h1>
    <a href="{{ route('admin.faq.create') }}" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-500">+ Добавить</a>
</div>

<div class="overflow-hidden rounded-xl border border-slate-800">
    <table class="w-full text-left text-sm">
        <thead class="bg-slate-900 text-slate-500">
        <tr>
            <th class="px-4 py-3">Вопрос</th>
            <th class="px-4 py-3">Язык</th>
            <th class="px-4 py-3">Статус</th>
            <th class="px-4 py-3"></th>
        </tr>
        </thead>
        <tbody class="divide-y divide-slate-800 bg-slate-900/50">
        @forelse ($items as $item)
            <tr>
                <td class="px-4 py-3">{{ Str::limit($item->question, 60) }}</td>
                <td class="px-4 py-3">{{ $item->locale }}</td>
                <td class="px-4 py-3">
                    <span class="{{ $item->is_active ? 'text-emerald-400' : 'text-slate-500' }}">
                        {{ $item->is_active ? 'Активен' : 'Выкл' }}
                    </span>
                </td>
                <td class="px-4 py-3 text-right">
                    <a href="{{ route('admin.faq.edit', $item) }}" class="text-emerald-400 hover:underline">Изменить</a>
                    <form method="POST" action="{{ route('admin.faq.destroy', $item) }}" class="inline" onsubmit="return confirm('Удалить?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="ml-3 text-red-400 hover:underline">Удалить</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="4" class="px-4 py-8 text-center text-slate-500">FAQ пуст — добавьте первый вопрос</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $items->links() }}</div>
@endsection
