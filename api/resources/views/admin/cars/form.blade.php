@extends('admin.layouts.app')
@section('title', $car->exists ? 'Редактировать авто' : 'Новое авто')
@section('content')
<div class="mb-6 flex items-center justify-between">
    <a href="{{ $car->exists ? route('admin.cars.show', $car) : route('admin.cars.index') }}" class="text-sm text-slate-500 hover:text-emerald-400">← Назад</a>
    @if($car->exists)
    <span class="text-sm text-slate-500">ID: {{ $car->id }}</span>
    @endif
</div>
<h1 class="mb-6 text-2xl font-bold">{{ $car->exists ? 'Редактировать' : 'Добавить' }} автомобиль</h1>

<form method="POST" action="{{ $car->exists ? route('admin.cars.update', $car) : route('admin.cars.store') }}"
      enctype="multipart/form-data" class="max-w-3xl space-y-4 rounded-xl border border-slate-800 bg-slate-900 p-6">
    @csrf
    @if ($car->exists) @method('PUT') @endif

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label class="text-sm text-slate-400">Марка *</label>
            <input name="make" value="{{ old('make', $car->make) }}" required class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-white">
        </div>
        <div>
            <label class="text-sm text-slate-400">Модель *</label>
            <input name="model" value="{{ old('model', $car->model) }}" required class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-white">
        </div>
        <div>
            <label class="text-sm text-slate-400">Год</label>
            <input type="number" name="year" value="{{ old('year', $car->year) }}" class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-white">
        </div>
        <div>
            <label class="text-sm text-slate-400">VIN</label>
            <input name="vin" value="{{ old('vin', $car->vin) }}" class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-white">
        </div>
        <div>
            <label class="text-sm text-slate-400">Цена</label>
            <input type="number" step="0.01" name="price" value="{{ old('price', $car->price) }}" class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-white">
        </div>
        <div>
            <label class="text-sm text-slate-400">Валюта</label>
            <input name="currency" value="{{ old('currency', $car->currency ?? 'USD') }}" class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-white">
        </div>
        <div>
            <label class="text-sm text-slate-400">Статус</label>
            <select name="status" class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-white">
                @foreach (['draft','published','reserved','sold','archived'] as $s)
                <option value="{{ $s }}" @selected(old('status', $car->status) === $s)>{{ $s }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div>
        <label class="text-sm text-slate-400">Описание</label>
        <textarea name="description" rows="4" class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-white">{{ old('description', $car->description) }}</textarea>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
        <div>
            <label class="text-sm text-slate-400">Пробег</label>
            <input name="mileage" value="{{ old('mileage', $car->specs['mileage'] ?? '') }}" class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-white">
        </div>
        <div>
            <label class="text-sm text-slate-400">Двигатель</label>
            <input name="engine" value="{{ old('engine', $car->specs['engine'] ?? '') }}" class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-white">
        </div>
        <div>
            <label class="text-sm text-slate-400">КПП</label>
            <input name="transmission" value="{{ old('transmission', $car->specs['transmission'] ?? '') }}" class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-white">
        </div>
    </div>

    <div class="rounded-lg border border-slate-700 bg-slate-800/50 p-4">
        <label class="text-sm font-medium text-slate-300">Фотографии</label>
        <p class="mt-1 text-xs text-slate-500">JPEG/PNG до {{ config('cars.photos.max_upload_kb') }} КБ. Файлы &gt; {{ config('cars.photos.compress_above_kb') }} КБ сжимаются автоматически (GD).</p>
        <input type="file" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple class="mt-2 w-full text-sm text-slate-400">
    </div>

    @if ($car->exists && $car->media->count())
    <div class="space-y-2">
        <p class="text-sm text-slate-400">Текущие фото (отметьте для удаления). Порядок = порядок ID через запятую в поле ниже:</p>
        <input type="hidden" name="media_order" id="media_order" value="{{ $car->media->pluck('id')->join(',') }}">
        <div class="flex flex-wrap gap-3" id="media-grid">
            @foreach ($car->media as $media)
            <div class="relative rounded-lg border border-slate-700 p-1" data-id="{{ $media->id }}">
                <img src="{{ $media->publicUrl() }}" class="h-24 w-32 rounded object-cover">
                <label class="mt-1 flex items-center gap-1 text-xs text-red-400">
                    <input type="checkbox" name="delete_media[]" value="{{ $media->id }}"> Удалить
                </label>
            </div>
            @endforeach
        </div>
        <p class="text-xs text-slate-500">Первое фото — главное в Telegram.</p>
    </div>
    @endif

    @if ($aiAvailable ?? false)
    <div class="rounded-lg border border-violet-500/30 bg-violet-500/5 p-4 space-y-3">
        <p class="text-sm font-medium text-violet-200">ИИ при сохранении</p>
        <label class="flex items-center gap-2 text-sm text-slate-300">
            <input type="checkbox" name="ai_enrich" value="1" @checked(old('ai_enrich', true)) class="rounded border-slate-600 text-violet-500">
            Сгенерировать описание, ключевые слова, фильтры и промпты для бота
        </label>
        <label class="flex items-center gap-2 text-sm text-slate-400">
            <input type="checkbox" name="ai_overwrite_description" value="1" @checked(old('ai_overwrite_description')) class="rounded border-slate-600">
            Перезаписать описание, если уже заполнено
        </label>
    </div>
    @else
    <p class="text-sm text-amber-400/90">ИИ недоступен — задайте GROQ_API_KEY в .env для автогенерации контента.</p>
    @endif

    <div class="flex gap-3">
        <button type="submit" class="rounded-lg bg-emerald-600 px-6 py-2 text-white hover:bg-emerald-500">Сохранить</button>
        @if($car->exists)
        <a href="{{ route('admin.cars.show', $car) }}" class="rounded-lg border border-slate-700 px-6 py-2 text-slate-300 hover:bg-slate-800">Отмена</a>
        @endif
    </div>
</form>
@endsection
