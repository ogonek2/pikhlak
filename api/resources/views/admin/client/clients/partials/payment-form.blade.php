@php
    $p = $payment;
    $input = 'w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-white';
    $label = 'mb-1 block text-xs font-medium text-slate-500';
@endphp
<div class="rounded-xl border border-dashed border-slate-700/80 bg-slate-950/30 p-4">
    <h3 class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $title }}</h3>
    <form method="POST" action="{{ $action }}" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        @csrf
        @if ($method !== 'POST') @method($method) @endif
        <div>
            <label class="{{ $label }}">Тип</label>
            <select name="type" class="{{ $input }}">
                @foreach ($paymentTypes as $key => $lbl)
                    <option value="{{ $key }}" @selected(old('type', $p?->type ?? 'rent') === $key)>{{ $lbl }}</option>
                @endforeach
            </select>
        </div>
        <div><label class="{{ $label }}">Сумма</label><input name="amount" type="number" step="0.01" value="{{ old('amount', $p?->amount) }}" required class="{{ $input }}"></div>
        <div><label class="{{ $label }}">Срок оплаты</label><input name="due_date" type="date" value="{{ old('due_date', $p?->due_date?->format('Y-m-d') ?? now()->format('Y-m-d')) }}" required class="{{ $input }}"></div>
        <div>
            <label class="{{ $label }}">Статус</label>
            <select name="status" class="{{ $input }}">
                @foreach ($paymentStatuses as $key => $lbl)
                    <option value="{{ $key }}" @selected(old('status', $p?->status ?? 'pending') === $key)>{{ $lbl }}</option>
                @endforeach
            </select>
        </div>
        <div><label class="{{ $label }}">Дата оплаты</label><input name="paid_at" type="date" value="{{ old('paid_at', $p?->paid_at?->format('Y-m-d')) }}" class="{{ $input }}"></div>
        <div class="sm:col-span-3"><label class="{{ $label }}">Примечание</label><input name="notes" value="{{ old('notes', $p?->notes) }}" class="{{ $input }}"></div>
        <div class="flex items-end gap-3">
            <button type="submit" class="rounded-lg bg-sky-600 px-4 py-2 text-sm font-medium text-white hover:bg-sky-500">{{ $method === 'POST' ? 'Добавить' : 'Сохранить' }}</button>
            @if ($method !== 'POST')
                <a href="{{ route('admin.client.clients.show', [$client, 'tab' => $tab ?? 'payments']) }}" class="text-sm text-slate-500 hover:text-slate-300">Отмена</a>
            @endif
        </div>
    </form>
</div>
