@php
    $ins = $insurance;
    $input = 'w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-white';
    $label = 'mb-1 block text-xs font-medium text-slate-500';
@endphp
<div class="mt-6 rounded-xl border border-dashed border-slate-700/80 bg-slate-950/30 p-4">
    <h3 class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $title }}</h3>
    <form method="POST" action="{{ $action }}" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
        @csrf
        @if ($method !== 'POST') @method($method) @endif
        <div><label class="{{ $label }}">Страховая</label><input name="provider" value="{{ old('provider', $ins?->provider) }}" required class="{{ $input }}"></div>
        <div><label class="{{ $label }}">Номер полиса</label><input name="policy_number" value="{{ old('policy_number', $ins?->policy_number) }}" class="{{ $input }}"></div>
        <div><label class="{{ $label }}">Премия</label><input name="premium_amount" type="number" step="0.01" value="{{ old('premium_amount', $ins?->premium_amount) }}" class="{{ $input }}"></div>
        <div><label class="{{ $label }}">Действует с</label><input name="valid_from" type="date" value="{{ old('valid_from', $ins?->valid_from?->format('Y-m-d')) }}" class="{{ $input }}"></div>
        <div><label class="{{ $label }}">Действует до</label><input name="valid_until" type="date" value="{{ old('valid_until', $ins?->valid_until?->format('Y-m-d')) }}" class="{{ $input }}"></div>
        <div class="flex items-end gap-3">
            <button type="submit" class="rounded-lg bg-sky-600 px-4 py-2 text-sm font-medium text-white hover:bg-sky-500">{{ $method === 'POST' ? 'Добавить' : 'Сохранить' }}</button>
            @if ($method !== 'POST')
                <a href="{{ route('admin.client.clients.show', [$client, 'tab' => $tab ?? 'insurances']) }}" class="text-sm text-slate-500 hover:text-slate-300">Отмена</a>
            @endif
        </div>
        <div class="sm:col-span-3"><label class="{{ $label }}">Покрытие / примечания</label><textarea name="coverage_notes" rows="2" class="{{ $input }}">{{ old('coverage_notes', $ins?->coverage_notes) }}</textarea></div>
    </form>
</div>
