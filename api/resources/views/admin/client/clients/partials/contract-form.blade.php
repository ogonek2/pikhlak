@php
    $c = $contract;
    $input = 'w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-white';
    $label = 'mb-1 block text-xs font-medium text-slate-500';
@endphp
<div class="mt-6 rounded-xl border border-dashed border-slate-700/80 bg-slate-950/30 p-4">
    <h3 class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $title }}</h3>
    <form method="POST" action="{{ $action }}" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
        @csrf
        @if ($method !== 'POST') @method($method) @endif
        <div><label class="{{ $label }}">Номер договора</label><input name="contract_number" value="{{ old('contract_number', $c?->contract_number) }}" class="{{ $input }} font-mono" placeholder="PK-2024-001"></div>
        <div>
            <label class="{{ $label }}">Автомобиль</label>
            <select name="rental_client_vehicle_id" class="{{ $input }}">
                <option value="">—</option>
                @foreach ($client->vehicles as $veh)
                    <option value="{{ $veh->id }}" @selected(old('rental_client_vehicle_id', $c?->rental_client_vehicle_id) == $veh->id)>{{ $veh->title() }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="{{ $label }}">Статус</label>
            <select name="status" class="{{ $input }}">
                @foreach ($contractStatuses as $key => $lbl)
                    <option value="{{ $key }}" @selected(old('status', $c?->status ?? 'active') === $key)>{{ $lbl }}</option>
                @endforeach
            </select>
        </div>
        <div><label class="{{ $label }}">Начало</label><input name="rent_start" type="date" value="{{ old('rent_start', $c?->rent_start?->format('Y-m-d') ?? now()->format('Y-m-d')) }}" required class="{{ $input }}"></div>
        <div><label class="{{ $label }}">Окончание</label><input name="rent_end" type="date" value="{{ old('rent_end', $c?->rent_end?->format('Y-m-d')) }}" class="{{ $input }}"></div>
        <div><label class="{{ $label }}">Платёж / 4 нед.</label><input name="monthly_amount" type="number" step="0.01" value="{{ old('monthly_amount', $c?->monthly_amount) }}" required class="{{ $input }}"></div>
        <div><label class="{{ $label }}">Сумма договора</label><input name="total_amount" type="number" step="0.01" value="{{ old('total_amount', $c?->total_amount) }}" class="{{ $input }}"></div>
        <div>
            <label class="{{ $label }}">Валюта</label>
            <select name="currency" class="{{ $input }}">
                @foreach (['UAH', 'USD', 'EUR'] as $cur)
                    <option value="{{ $cur }}" @selected(old('currency', $c?->currency ?? 'UAH') === $cur)>{{ $cur }}</option>
                @endforeach
            </select>
        </div>
        <div class="sm:col-span-2 flex flex-wrap items-center gap-4">
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="buyout_option" value="1" @checked(old('buyout_option', $c?->buyout_option ?? true)) class="rounded border-slate-600 bg-slate-800 text-sky-500"> Право выкупа</label>
            <button type="submit" class="rounded-lg bg-sky-600 px-4 py-2 text-sm font-medium text-white hover:bg-sky-500">{{ $method === 'POST' ? 'Добавить' : 'Сохранить' }}</button>
            @if ($method !== 'POST')
                <a href="{{ route('admin.client.clients.show', [$client, 'tab' => $tab ?? 'contracts']) }}" class="text-sm text-slate-500 hover:text-slate-300">Отмена</a>
            @endif
        </div>
        <div class="sm:col-span-3"><label class="{{ $label }}">Примечания</label><textarea name="notes" rows="2" class="{{ $input }}">{{ old('notes', $c?->notes) }}</textarea></div>
    </form>
</div>
