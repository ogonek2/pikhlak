@extends('admin.layouts.app')
@section('title', $car->title())
@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <a href="{{ route('admin.cars.index') }}" class="text-sm text-slate-500 hover:text-emerald-400">← Каталог</a>
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('admin.cars.edit', $car) }}" class="rounded-lg border border-emerald-500/50 px-4 py-2 text-sm text-emerald-400 hover:bg-emerald-500/10">Редактировать</a>
        @if($aiAvailable ?? false)
        <form method="POST" action="{{ route('admin.cars.generate-ai', $car) }}">@csrf
            <input type="hidden" name="overwrite" value="1">
            <button type="submit" class="rounded-lg border border-violet-500/50 px-4 py-2 text-sm text-violet-300 hover:bg-violet-500/10">⟳ ИИ контент</button>
        </form>
        @endif
        <form method="POST" action="{{ route('admin.cars.duplicate', $car) }}">@csrf
            <button type="submit" class="rounded-lg border border-slate-700 px-4 py-2 text-sm text-slate-300 hover:bg-slate-800">Дублировать</button>
        </form>
        <form method="POST" action="{{ route('admin.cars.destroy', $car) }}" onsubmit="return confirm('Удалить авто?')">@csrf @method('DELETE')
            <button type="submit" class="rounded-lg border border-red-500/50 px-4 py-2 text-sm text-red-400 hover:bg-red-500/10">Удалить</button>
        </form>
    </div>
</div>

<div class="grid gap-6 lg:grid-cols-3">
    <div class="lg:col-span-2 space-y-4">
        <div class="rounded-xl border border-slate-800 bg-slate-900 p-6">
            <h1 class="text-2xl font-bold text-white">{{ $car->title() }}</h1>
            <p class="mt-1 text-lg text-emerald-400">{{ $car->formattedPrice() }}</p>
            <div class="mt-4 flex flex-wrap gap-2 text-xs">
                <span class="rounded-full bg-slate-800 px-2 py-1">ID: {{ $car->id }}</span>
                <span class="rounded-full px-2 py-1 {{ $car->status === 'published' ? 'bg-emerald-500/20 text-emerald-400' : 'bg-slate-700 text-slate-400' }}">{{ $car->status }}</span>
                @if($car->vin)<span class="rounded-full bg-slate-800 px-2 py-1">VIN: {{ $car->vin }}</span>@endif
            </div>
            @if($car->description)
            <div class="mt-4 text-slate-300 whitespace-pre-wrap">{!! $car->description !!}</div>
            @endif
            @if($car->specs)
            <dl class="mt-4 grid grid-cols-2 gap-2 text-sm">
                @foreach($car->specs as $k => $v)
                <div><dt class="text-slate-500">{{ $k }}</dt><dd class="text-white">{{ $v }}</dd></div>
                @endforeach
            </dl>
            @endif
        </div>

        @php $meta = $car->ai_meta ?? []; @endphp
        @if(!empty($meta))
        <div class="rounded-xl border border-violet-500/30 bg-violet-500/5 p-6">
            <h2 class="mb-4 text-lg font-semibold text-violet-200">ИИ-материалы для бота и поиска</h2>
            @if(!empty($meta['generated_at']))
            <p class="mb-3 text-xs text-slate-500">Сгенерировано: {{ $meta['generated_at'] }}</p>
            @endif
            @if(!empty($meta['description_short']))
            <p class="mb-3 text-sm text-slate-300"><span class="text-slate-500">Кратко:</span> {{ $meta['description_short'] }}</p>
            @endif
            @if(!empty($meta['bot_context']))
            <div class="mb-3">
                <div class="text-xs font-semibold uppercase text-slate-500">Промпт для ИИ-ассистента</div>
                <p class="mt-1 text-sm text-slate-300">{{ $meta['bot_context'] }}</p>
            </div>
            @endif
            @if(!empty($meta['keywords']))
            <div class="mb-3">
                <div class="text-xs font-semibold uppercase text-slate-500">Ключевые слова</div>
                <div class="mt-1 flex flex-wrap gap-1">
                    @foreach($meta['keywords'] as $kw)
                    <span class="rounded bg-slate-800 px-2 py-0.5 text-xs text-emerald-300">{{ $kw }}</span>
                    @endforeach
                </div>
            </div>
            @endif
            @if(!empty($meta['search_aliases']))
            <div class="mb-3">
                <div class="text-xs font-semibold uppercase text-slate-500">Алиасы поиска в чате</div>
                <div class="mt-1 flex flex-wrap gap-1">
                    @foreach($meta['search_aliases'] as $a)
                    <span class="rounded bg-slate-800 px-2 py-0.5 text-xs text-violet-300">{{ $a }}</span>
                    @endforeach
                </div>
            </div>
            @endif
            @if(!empty($meta['filters']))
            <div class="mb-3">
                <div class="text-xs font-semibold uppercase text-slate-500">Фильтры</div>
                <dl class="mt-1 grid grid-cols-2 gap-2 text-sm">
                    @foreach($meta['filters'] as $fk => $fv)
                    @if(is_string($fv) || is_numeric($fv))
                    <div><dt class="text-slate-500">{{ $fk }}</dt><dd class="text-white">{{ $fv }}</dd></div>
                    @endif
                    @endforeach
                </dl>
            </div>
            @endif
            @if(!empty($meta['photo_prompt_phrases']))
            <div class="mb-3">
                <div class="text-xs font-semibold uppercase text-slate-500">Фразы для запроса фото</div>
                <p class="mt-1 text-xs text-slate-400">{{ implode(' · ', $meta['photo_prompt_phrases']) }}</p>
            </div>
            @endif
            @if(!empty($meta['referral_hints']))
            <div>
                <div class="text-xs font-semibold uppercase text-slate-500">Идеи для реф. ссылок</div>
                <ul class="mt-1 list-inside list-disc text-sm text-slate-400">
                    @foreach($meta['referral_hints'] as $hint)
                    <li>{{ $hint }}</li>
                    @endforeach
                </ul>
            </div>
            @endif
        </div>
        @endif
    </div>
    <div class="rounded-xl border border-slate-800 bg-slate-900 p-6">
        <h2 class="mb-3 font-semibold">Фотографии ({{ $car->media->count() }})</h2>
        @if($car->media->isEmpty())
            <p class="text-sm text-slate-500">Нет фото. Добавьте в редактировании.</p>
        @else
            <div class="grid grid-cols-2 gap-2">
                @foreach ($car->media as $media)
                <a href="{{ $media->publicUrl() }}" target="_blank" class="block overflow-hidden rounded-lg">
                    <img src="{{ $media->publicUrl() }}" class="h-24 w-full object-cover" alt="">
                </a>
                @endforeach
            </div>
        @endif
        <p class="mt-3 text-xs text-slate-500">В Telegram клиенту отправляются при запросе фото по этой модели.</p>
    </div>
</div>
@endsection
