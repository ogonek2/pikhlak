@extends('admin.layouts.app')
@section('title', $link ? 'Редактировать ссылку' : 'Новая реф. ссылка')
@php
    $s = $link->settings ?? $defaultSettings;
    $isEdit = (bool) $link;
@endphp

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.referrals.index') }}" class="text-sm text-slate-500 hover:text-emerald-400">← К списку</a>
    <h1 class="mt-2 text-2xl font-bold">{{ $isEdit ? 'Редактировать' : 'Создать' }} реферальную ссылку</h1>
</div>

<form method="POST" action="{{ $isEdit ? route('admin.referrals.update', $link) : route('admin.referrals.store') }}" class="max-w-3xl space-y-8">
    @csrf
    @if ($isEdit) @method('PUT') @endif

    <section class="rounded-xl border border-slate-800 bg-slate-900 p-6 space-y-4">
        <h2 class="font-semibold text-white">Основное</h2>
        <div>
            <label class="mb-1 block text-sm text-slate-400">Название (для себя) *</label>
            <input name="name" value="{{ old('name', $link->name ?? '') }}" required
                   placeholder="Instagram — Kia K5 май"
                   class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-white">
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm text-slate-400">Тип ссылки *</label>
                <select name="type" id="ref-type" required class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-white">
                    @foreach ($types as $k => $label)
                        <option value="{{ $k }}" @selected(old('type', $link->type ?? 'traffic') === $k)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm text-slate-400">Канал / метка публикации</label>
                <select name="channel" class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-white">
                    <option value="">— не указан —</option>
                    @foreach ($channels as $k => $label)
                        <option value="{{ $k }}" @selected(old('channel', $link->channel ?? '') === $k)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div>
            <label class="mb-1 block text-sm text-slate-400">Код в ссылке (латиница, цифры, _ -)</label>
            <input name="code" value="{{ old('code', $link->code ?? '') }}"
                   placeholder="Авто: pk_ig_k5 или оставьте пустым"
                   class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 font-mono text-sm text-white">
            <p class="mt-1 text-xs text-slate-500">Telegram: t.me/бот?start=<b>КОД</b> (до 64 символов)</p>
        </div>
        <div>
            <label class="mb-1 block text-sm text-slate-400">Кампания (группировка)</label>
            <select name="campaign_id" class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-white">
                <option value="">— без кампании —</option>
                @foreach ($campaigns as $c)
                    <option value="{{ $c->id }}" @selected(old('campaign_id', $link->campaign_id ?? '') == $c->id)>{{ $c->name }}</option>
                @endforeach
            </select>
        </div>
        <label class="flex items-center gap-2 text-sm text-slate-300">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $link->is_active ?? true)) class="rounded border-slate-600">
            Ссылка активна
        </label>
    </section>

    <section id="block-car" class="rounded-xl border border-slate-800 bg-slate-900 p-6 space-y-4">
        <h2 class="font-semibold text-emerald-400">Привязка к авто</h2>
        <select name="car_id" class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-white">
            <option value="">— выберите авто —</option>
            @foreach ($cars as $car)
                <option value="{{ $car->id }}" @selected(old('car_id', $link->car_id ?? '') == $car->id)>
                    {{ $car->make }} {{ $car->model }} {{ $car->year }} — {{ number_format($car->price) }} {{ $car->currency }}
                </option>
            @endforeach
        </select>
        <label class="flex items-center gap-2 text-sm text-slate-300">
            <input type="checkbox" name="setting_auto_show_car" value="1" @checked(old('setting_auto_show_car', $s['auto_show_car'] ?? true)) class="rounded border-slate-600">
            Показать фото авто сразу после /start
        </label>
        <label class="flex items-center gap-2 text-sm text-slate-300">
            <input type="checkbox" name="setting_pin_car_in_context" value="1" @checked(old('setting_pin_car_in_context', $s['pin_car_in_context'] ?? true)) class="rounded border-slate-600">
            Закрепить интерес к авто в лиде
        </label>
    </section>

    <section id="block-partner" class="rounded-xl border border-slate-800 bg-slate-900 p-6 space-y-4">
        <h2 class="font-semibold text-violet-400">Посредник / реферал</h2>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm text-slate-400">Имя партнёра</label>
                <input name="partner_name" value="{{ old('partner_name', $link->partner_name ?? '') }}"
                       class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-white">
            </div>
            <div>
                <label class="mb-1 block text-sm text-slate-400">Контакт</label>
                <input name="partner_contact" value="{{ old('partner_contact', $link->partner_contact ?? '') }}"
                       class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-white">
            </div>
        </div>
        <div>
            <label class="mb-1 block text-sm text-slate-400">Комиссия %</label>
            <input type="number" step="0.01" name="partner_commission_percent" value="{{ old('partner_commission_percent', $link->partner_commission_percent ?? '') }}"
                   class="w-32 rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-white">
        </div>
        @if ($managers->isNotEmpty())
        <div>
            <label class="mb-1 block text-sm text-slate-400">Назначить менеджера на лид</label>
            <select name="manager_id" class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-white">
                <option value="">— автоматически —</option>
                @foreach ($managers as $m)
                    <option value="{{ $m->id }}" @selected(old('manager_id', $link->manager_id ?? '') == $m->id)>{{ $m->user->name ?? 'Manager #'.$m->id }}</option>
                @endforeach
            </select>
        </div>
        @endif
    </section>

    <section class="rounded-xl border border-slate-800 bg-slate-900 p-6 space-y-4">
        <h2 class="font-semibold text-white">UTM-метки (аналитика)</h2>
        <div class="grid gap-3 sm:grid-cols-2">
            <input name="utm_source" value="{{ old('utm_source', $link->utm_source ?? '') }}" placeholder="utm_source" class="rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-white">
            <input name="utm_medium" value="{{ old('utm_medium', $link->utm_medium ?? '') }}" placeholder="utm_medium (telegram)" class="rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-white">
            <input name="utm_campaign" value="{{ old('utm_campaign', $link->utm_campaign ?? '') }}" placeholder="utm_campaign" class="rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-white">
            <input name="utm_content" value="{{ old('utm_content', $link->utm_content ?? '') }}" placeholder="utm_content" class="rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-white">
            <input name="utm_term" value="{{ old('utm_term', $link->utm_term ?? '') }}" placeholder="utm_term" class="rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-white sm:col-span-2">
        </div>
    </section>

    <section class="rounded-xl border border-slate-800 bg-slate-900 p-6 space-y-4">
        <h2 class="font-semibold text-white">Поведение и лимиты</h2>
        <div>
            <label class="mb-1 block text-sm text-slate-400">Приветствие после перехода (HTML)</label>
            <textarea name="landing_message" rows="3" class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-white"
                      placeholder="Добро пожаловать! Вы перешли с Instagram по акции Kia K5.">{{ old('landing_message', $link->landing_message ?? '') }}</textarea>
        </div>
        <div>
            <label class="mb-1 block text-sm text-slate-400">Заметка (только в админке)</label>
            <textarea name="description" rows="2" class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-white">{{ old('description', $link->description ?? '') }}</textarea>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm text-slate-400">Срок действия</label>
                <input type="datetime-local" name="expires_at" value="{{ old('expires_at', isset($link->expires_at) ? $link->expires_at->format('Y-m-d\TH:i') : '') }}"
                       class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-white">
            </div>
            <div>
                <label class="mb-1 block text-sm text-slate-400">Макс. переходов</label>
                <input type="number" name="max_starts" value="{{ old('max_starts', $link->max_starts ?? '') }}" min="1"
                       class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-white">
            </div>
        </div>
        <div>
            <label class="mb-1 block text-sm text-slate-400">Бонус к warming при создании лида</label>
            <input type="number" name="setting_assign_warming_bonus" value="{{ old('setting_assign_warming_bonus', $s['assign_warming_bonus'] ?? 5) }}" min="0" max="30"
                   class="w-24 rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-white">
        </div>
        <div>
            <label class="mb-1 block text-sm text-slate-400">Теги (через запятую)</label>
            <input name="setting_tags" value="{{ old('setting_tags', implode(', ', $s['tags'] ?? [])) }}"
                   class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-white">
        </div>
    </section>

    <button type="submit" class="rounded-lg bg-emerald-600 px-6 py-2.5 font-medium text-white hover:bg-emerald-500">
        {{ $isEdit ? 'Сохранить' : 'Создать ссылку' }}
    </button>
</form>

<script>
    const typeSel = document.getElementById('ref-type');
    const blockCar = document.getElementById('block-car');
    const blockPartner = document.getElementById('block-partner');
    function toggleBlocks() {
        const t = typeSel.value;
        blockCar.style.display = (t === 'car') ? 'block' : 'none';
        blockPartner.style.display = (t === 'partner') ? 'block' : 'none';
    }
    typeSel.addEventListener('change', toggleBlocks);
    toggleBlocks();
</script>
@endsection
