@php
    $m = $maintenance;
    $input = 'w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-white';
    $label = 'mb-1 block text-xs font-medium text-slate-500';
@endphp
<div class="mt-6 rounded-xl border border-dashed border-slate-700/80 bg-slate-950/30 p-4">
    <h3 class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $title }}</h3>
    <form method="POST" action="{{ $action }}" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
        @csrf
        @if ($method !== 'POST') @method($method) @endif
        <div><label class="{{ $label }}">Название</label><input name="title" value="{{ old('title', $m?->title) }}" required class="{{ $input }}" placeholder="Плановое ТО"></div>
        <div>
            <label class="{{ $label }}">Тип</label>
            <select name="type" class="{{ $input }}">
                @foreach ($maintenanceTypes as $key => $lbl)
                    <option value="{{ $key }}" @selected(old('type', $m?->type ?? 'service') === $key)>{{ $lbl }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="{{ $label }}">Статус</label>
            <select name="status" class="{{ $input }}">
                @foreach ($maintenanceStatuses as $key => $lbl)
                    <option value="{{ $key }}" @selected(old('status', $m?->status ?? 'planned') === $key)>{{ $lbl }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="{{ $label }}">Автомобиль</label>
            <select name="rental_client_vehicle_id" class="{{ $input }}">
                <option value="">—</option>
                @foreach ($client->vehicles as $veh)
                    <option value="{{ $veh->id }}" @selected(old('rental_client_vehicle_id', $m?->rental_client_vehicle_id) == $veh->id)>{{ $veh->title() }}</option>
                @endforeach
            </select>
        </div>
        <div><label class="{{ $label }}">Запланировано</label><input name="scheduled_at" type="date" value="{{ old('scheduled_at', $m?->scheduled_at?->format('Y-m-d')) }}" class="{{ $input }}"></div>
        <div><label class="{{ $label }}">Выполнено</label><input name="completed_at" type="date" value="{{ old('completed_at', $m?->completed_at?->format('Y-m-d')) }}" class="{{ $input }}"></div>
        <div><label class="{{ $label }}">Пробег</label><input name="mileage_at" type="number" value="{{ old('mileage_at', $m?->mileage_at) }}" class="{{ $input }}"></div>
        <div><label class="{{ $label }}">Стоимость</label><input name="cost" type="number" step="0.01" value="{{ old('cost', $m?->cost) }}" class="{{ $input }}"></div>
        <div class="flex items-end gap-3">
            <button type="submit" class="rounded-lg bg-sky-600 px-4 py-2 text-sm font-medium text-white hover:bg-sky-500">{{ $method === 'POST' ? 'Добавить' : 'Сохранить' }}</button>
            @if ($method !== 'POST')
                <a href="{{ route('admin.client.clients.show', [$client, 'tab' => $tab ?? 'maintenances']) }}" class="text-sm text-slate-500 hover:text-slate-300">Отмена</a>
            @endif
        </div>
        <div class="sm:col-span-3"><label class="{{ $label }}">Примечания</label><textarea name="notes" rows="2" class="{{ $input }}">{{ old('notes', $m?->notes) }}</textarea></div>
    </form>
</div>
