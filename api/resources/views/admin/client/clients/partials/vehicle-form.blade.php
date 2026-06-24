@php
    $v = $vehicle;
    $input = 'w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-white';
    $label = 'mb-1 block text-xs font-medium text-slate-500';
@endphp
<div class="mt-6 rounded-xl border border-dashed border-slate-700/80 bg-slate-950/30 p-4">
    <h3 class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $title }}</h3>
    <form method="POST" action="{{ $action }}" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
        @csrf
        @if ($method !== 'POST') @method($method) @endif
        <div><label class="{{ $label }}">Марка</label><input name="make" value="{{ old('make', $v?->make) }}" required class="{{ $input }}"></div>
        <div><label class="{{ $label }}">Модель</label><input name="model" value="{{ old('model', $v?->model) }}" required class="{{ $input }}"></div>
        <div><label class="{{ $label }}">Год</label><input name="year" type="number" value="{{ old('year', $v?->year) }}" class="{{ $input }}"></div>
        <div><label class="{{ $label }}">Гос. номер</label><input name="plate_number" value="{{ old('plate_number', $v?->plate_number) }}" class="{{ $input }}"></div>
        <div><label class="{{ $label }}">VIN</label><input name="vin" value="{{ old('vin', $v?->vin) }}" class="{{ $input }} font-mono text-xs"></div>
        <div><label class="{{ $label }}">Пробег, км</label><input name="mileage" type="number" value="{{ old('mileage', $v?->mileage) }}" class="{{ $input }}"></div>
        <div class="sm:col-span-2 flex items-center gap-4">
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_current" value="1" @checked(old('is_current', $v?->is_current ?? true)) class="rounded border-slate-600 bg-slate-800 text-sky-500"> Текущий автомобиль</label>
            <button type="submit" class="rounded-lg bg-sky-600 px-4 py-2 text-sm font-medium text-white hover:bg-sky-500">{{ $method === 'POST' ? 'Добавить' : 'Сохранить' }}</button>
            @if ($method !== 'POST')
                <a href="{{ route('admin.client.clients.show', [$client, 'tab' => $tab ?? 'vehicles']) }}" class="text-sm text-slate-500 hover:text-slate-300">Отмена</a>
            @endif
        </div>
    </form>
</div>
