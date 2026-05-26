<form method="POST" action="{{ $action }}" class="space-y-3">
    @csrf
    @if ($method === 'PUT') @method('PUT') @endif
    <div class="grid gap-3 sm:grid-cols-2">
        <div>
            <label class="mb-1 block text-xs text-slate-500">Название</label>
            <input name="name" value="{{ old('name', $route->name) }}" placeholder="Например: Каталог и цены" required
                   class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-white">
        </div>
        <div>
            <label class="mb-1 block text-xs text-slate-500">Slug (латиница)</label>
            <input name="slug" value="{{ old('slug', $route->slug) }}" placeholder="catalog, warming…" required
                   class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 font-mono text-white"
                   @if($route->slug === 'default') readonly @endif>
        </div>
    </div>
    <div>
        <label class="mb-1 block text-xs text-slate-500">Ключевые слова (через запятую)</label>
        <input name="intent_keywords_raw" value="{{ old('intent_keywords_raw', implode(', ', $route->intent_keywords ?? [])) }}"
               placeholder="купить, kia, каталог, цена, доставка"
               class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-white">
    </div>
    <div class="grid gap-3 sm:grid-cols-3">
        <div>
            <label class="mb-1 block text-xs text-slate-500">Модель ИИ</label>
            <select name="model_id" class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-white">
                <option value="">По умолчанию</option>
                @foreach ($models as $m)
                <option value="{{ $m->id }}" @selected(old('model_id', $route->model_id) == $m->id)>
                    {{ $m->config['label'] ?? $m->model_name }} ({{ $m->provider }})
                </option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="mb-1 block text-xs text-slate-500">Профиль</label>
            <select name="profile_id" class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-white">
                <option value="">По умолчанию</option>
                @foreach ($profiles as $p)
                <option value="{{ $p->id }}" @selected(old('profile_id', $route->profile_id) == $p->id)>{{ $p->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="mb-1 block text-xs text-slate-500">Приоритет (выше = важнее)</label>
            <input type="number" name="priority" value="{{ old('priority', $route->priority) }}" min="1" max="999" required
                   class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-white">
        </div>
    </div>
    <div>
        <p class="mb-2 text-xs text-slate-500">Источники данных для ИИ (не выдумывать факты):</p>
        <div class="flex flex-wrap gap-4 text-sm">
            @foreach (['cars' => 'Каталог авто', 'faq' => 'FAQ', 'leads' => 'CRM лиды'] as $key => $label)
            <label class="flex items-center gap-2">
                <input type="checkbox" name="data_sources[]" value="{{ $key }}"
                       @checked(in_array($key, old('data_sources', $route->data_sources ?? ['cars','faq','leads']))) class="text-emerald-500">
                {{ $label }}
            </label>
            @endforeach
        </div>
    </div>
    <div>
        <label class="mb-1 block text-xs text-slate-500">Инструкция для ИИ (скрыта от клиента)</label>
        <p class="mb-2 text-xs text-amber-400/90">Не попадает в чат как текст. Пишите, что должен сделать бот. Без score, CRM, «прогрев», процентов — иначе модель может процитировать.</p>
        <textarea name="extra_instruction" rows="3" placeholder="Пример: расскажи про пригон из Кореи, предложи покупку за наличные и аренду с выкупом…"
                  class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-white">{{ old('extra_instruction', $route->extra_instruction) }}</textarea>
    </div>
    <div>
        <label class="mb-1 block text-xs text-slate-500">Pipeline JSON (необязательно)</label>
        <textarea name="pipeline_json" rows="3" placeholder='Оставьте пустым или: [{"model_id": 1, "instruction": "шаг 2"}]'
                  class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 font-mono text-xs text-white">{{ old('pipeline_json', $route->pipeline ? json_encode($route->pipeline, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : '') }}</textarea>
        <p class="mt-1 text-xs text-slate-500">Необязательно. Неверный JSON или шаблон <code class="text-slate-400">{"steps": [...]}</code> игнорируется — ответ идёт одной моделью.</p>
    </div>
    <div class="flex flex-wrap items-center gap-4 border-t border-slate-800 pt-4">
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="is_active" value="1"
                   @checked((bool) old('is_active', $route->is_active))
                   class="text-emerald-500">
            Активен
        </label>
        <button type="submit" class="rounded-lg bg-emerald-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-emerald-500">
            {{ $submitLabel ?? 'Сохранить' }}
        </button>
        <a href="{{ route('admin.ai.routes') }}" class="text-sm text-slate-500 hover:text-white">← К списку</a>
    </div>
</form>
