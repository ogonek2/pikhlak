@extends('admin.layouts.app')
@section('title', 'Новый клиент')
@section('content')
<div class="mb-6">
    <a href="{{ route('admin.client.clients.index') }}" class="text-sm text-slate-500 hover:text-sky-400">← Клиенты</a>
    <h1 class="mt-2 text-2xl font-bold tracking-tight">Новый клиент</h1>
    <p class="mt-1 text-sm text-slate-500">Авто из каталога, калькулятор аренды с выкупом, автогенерация платежей и ТО</p>
</div>

<form method="POST" action="{{ route('admin.client.clients.store') }}" id="client-create-form" class="grid gap-6 lg:grid-cols-3">
    @csrf

    <div class="space-y-6 lg:col-span-2">
        <section class="rounded-2xl border border-slate-800 bg-slate-900/50 p-6">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Клиент</h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-xs text-slate-500">ФИО</label>
                    <input name="full_name" value="{{ old('full_name') }}" required
                           class="w-full rounded-lg border border-slate-700 bg-slate-950 px-4 py-2.5 text-white focus:border-sky-500/50 focus:outline-none">
                </div>
                <div>
                    <label class="mb-1 block text-xs text-slate-500">Телефон</label>
                    <input name="primary_phone" value="{{ old('primary_phone') }}" placeholder="+380..."
                           class="w-full rounded-lg border border-slate-700 bg-slate-950 px-4 py-2.5 text-white">
                </div>
                <div>
                    <label class="mb-1 block text-xs text-slate-500">Email</label>
                    <input name="email" type="email" value="{{ old('email') }}"
                           class="w-full rounded-lg border border-slate-700 bg-slate-950 px-4 py-2.5 text-white">
                </div>
                <div>
                    <label class="mb-1 block text-xs text-slate-500">Статус</label>
                    <select name="status" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-4 py-2.5 text-white">
                        @foreach ($statuses as $key => $label)
                            <option value="{{ $key }}" @selected(old('status', 'active') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs text-slate-500">Гос. номер</label>
                    <input name="plate_number" value="{{ old('plate_number') }}"
                           class="w-full rounded-lg border border-slate-700 bg-slate-950 px-4 py-2.5 text-white">
                </div>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-800 bg-slate-900/50 p-6">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Аренда с выкупом</h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-xs text-slate-500">Автомобиль из каталога</label>
                    <select name="car_id" id="car_id" required
                            class="w-full rounded-lg border border-slate-700 bg-slate-950 px-4 py-2.5 text-white">
                        <option value="">— выберите авто —</option>
                        @foreach ($cars as $car)
                            @php $sym = $currencySymbols[$car->currency] ?? $car->currency; @endphp
                            <option value="{{ $car->id }}"
                                    data-price="{{ $car->price }}"
                                    data-currency="{{ $car->currency }}"
                                    @selected(old('car_id') == $car->id)>
                                {{ $car->title() }} — {{ number_format((float) $car->price, 0, '.', ' ') }} {{ $sym }}
                                @if($car->status !== 'published') ({{ $car->status }}) @endif
                            </option>
                        @endforeach
                    </select>
                    @if ($cars->isEmpty())
                        <p class="mt-2 text-xs text-amber-400">В каталоге нет авто. <a href="{{ route('admin.cars.create') }}" class="underline">Добавить</a></p>
                    @endif
                </div>
                <div>
                    <label class="mb-1 block text-xs text-slate-500">Первый взнос</label>
                    <input name="first_payment" id="first_payment" type="number" step="0.01" min="0"
                           value="{{ old('first_payment', 2000) }}" required
                           class="w-full rounded-lg border border-slate-700 bg-slate-950 px-4 py-2.5 text-white">
                </div>
                <div>
                    <label class="mb-1 block text-xs text-slate-500">Срок аренды (лет)</label>
                    <input name="term_years" id="term_years" type="number" min="1" max="15"
                           value="{{ old('term_years', $defaults['term_years']) }}" required
                           class="w-full rounded-lg border border-slate-700 bg-slate-950 px-4 py-2.5 text-white">
                </div>
                <div>
                    <label class="mb-1 block text-xs text-slate-500">Переплата (% в год от остатка)</label>
                    <input name="overpayment_rate" id="overpayment_rate" type="number" step="0.01" min="0" max="100"
                           value="{{ old('overpayment_rate', $defaults['overpayment_rate'] * 100) }}"
                           class="w-full rounded-lg border border-slate-700 bg-slate-950 px-4 py-2.5 text-white">
                    <p class="mt-1 text-[10px] text-slate-600">По умолчанию 40%. В форму уходит как доля (0.4).</p>
                </div>
                <div>
                    <label class="mb-1 block text-xs text-slate-500">Начало договора</label>
                    <input name="rent_start" type="date" value="{{ old('rent_start', $defaults['rent_start']) }}"
                           class="w-full rounded-lg border border-slate-700 bg-slate-950 px-4 py-2.5 text-white">
                </div>
                <div>
                    <label class="mb-1 block text-xs text-slate-500">Номер договора</label>
                    <input name="contract_number" value="{{ old('contract_number') }}" placeholder="авто"
                           class="w-full rounded-lg border border-slate-700 bg-slate-950 px-4 py-2.5 text-white">
                </div>
            </div>
        </section>

        <div>
            <label class="mb-1 block text-xs text-slate-500">Заметки</label>
            <textarea name="notes" rows="3" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-4 py-2.5 text-white">{{ old('notes') }}</textarea>
        </div>

        <button type="submit" class="rounded-xl bg-sky-600 px-8 py-3 font-semibold text-white hover:bg-sky-500">
            Создать клиента и сгенерировать график
        </button>
    </div>

    <aside class="lg:col-span-1">
        <div class="sticky top-6 rounded-2xl border border-slate-800 bg-gradient-to-b from-slate-900 to-slate-950 p-5">
            <h2 class="text-sm font-semibold text-sky-300">Предпросмотр расчёта</h2>
            <p class="mt-1 text-xs text-slate-500">Итог для клиента — без деталей расчёта</p>
            <div id="calc-preview" class="mt-4 space-y-3 text-sm text-slate-300">
                <p class="text-slate-600">Выберите авто и параметры</p>
            </div>
            <dl id="calc-details" class="mt-4 hidden space-y-2 border-t border-slate-800 pt-4 text-xs">
                <div class="flex justify-between"><dt class="text-slate-500">Первый взнос</dt><dd id="v-down">—</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Итого за срок</dt><dd id="v-total" class="font-medium text-white">—</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Платёж / {{ config('rent_buyout.weeks_per_period') }} нед.</dt><dd id="v-period" class="font-medium text-sky-300">—</dd></div>
            </dl>
        </div>
    </aside>
</form>

<script>
(function () {
    const previewUrl = @json(route('admin.client.clients.calculator-preview'));
    const csrf = @json(csrf_token());
    const form = document.getElementById('client-create-form');
    const fields = ['car_id', 'first_payment', 'term_years', 'overpayment_rate'];
    let timer = null;

    const money = (n, cur) => Number(n).toLocaleString('uk-UA', { maximumFractionDigits: 0 }) + ' ' + (cur || '');

    function overpaymentFraction() {
        const pct = parseFloat(document.getElementById('overpayment_rate').value || '40');
        return pct > 1 ? pct / 100 : pct;
    }

    async function refresh() {
        const carId = document.getElementById('car_id').value;
        if (!carId) return;

        const body = {
            car_id: carId,
            first_payment: document.getElementById('first_payment').value,
            term_years: document.getElementById('term_years').value,
            overpayment_rate: overpaymentFraction(),
        };

        try {
            const res = await fetch(previewUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                body: JSON.stringify(body),
            });
            const json = await res.json();
            if (!res.ok) throw new Error(json.message || 'Ошибка расчёта');

            const d = json.data;
            const sym = d.currency;
            document.getElementById('calc-preview').innerHTML =
                '<p class="font-medium text-white">' + json.car.title + '</p>' +
                '<p class="text-xs text-slate-500">' + json.summary + '</p>';
            document.getElementById('calc-details').classList.remove('hidden');
            document.getElementById('v-down').textContent = money(d.first_payment, sym);
            document.getElementById('v-total').textContent = money(d.total_cost, sym);
            document.getElementById('v-period').textContent = money(d.period_payment, sym);
        } catch (e) {
            document.getElementById('calc-preview').innerHTML = '<p class="text-red-400 text-xs">' + e.message + '</p>';
        }
    }

    function schedule() {
        clearTimeout(timer);
        timer = setTimeout(refresh, 300);
    }

    fields.forEach((id) => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('input', schedule);
        if (el) el.addEventListener('change', schedule);
    });

    form.addEventListener('submit', () => {
        const rateInput = document.getElementById('overpayment_rate');
        rateInput.value = overpaymentFraction();
    });

    if (document.getElementById('car_id').value) refresh();
})();
</script>
@endsection
