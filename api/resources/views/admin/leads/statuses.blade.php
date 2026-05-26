@extends('admin.layouts.app')

@section('title', 'Статусы лидов')

@section('content')
<div class="mb-8">
    <h1 class="text-2xl font-bold">Pipeline — статусы лидов</h1>
</div>

<div class="space-y-4">
    @foreach ($statuses as $status)
        <form method="POST" action="{{ route('admin.lead-statuses.update', $status) }}"
              class="flex flex-wrap items-end gap-4 rounded-xl border border-slate-800 bg-slate-900 p-4">
            @csrf @method('PATCH')
            <div class="w-24 font-mono text-xs text-slate-500">{{ $status->code }}</div>
            <div class="flex-1 min-w-[200px]">
                <input type="text" name="name" value="{{ $status->name }}" class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-white">
            </div>
            <div class="w-28">
                <input type="text" name="color" value="{{ $status->color }}" placeholder="#hex"
                       class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-white">
            </div>
            <div class="w-20">
                <input type="number" name="sort" value="{{ $status->sort }}" class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-white">
            </div>
            <button type="submit" class="rounded-lg bg-slate-700 px-4 py-2 text-sm text-white hover:bg-slate-600">Сохранить</button>
        </form>
    @endforeach
</div>
@endsection
