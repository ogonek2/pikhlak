<form method="POST" action="{{ $action }}" class="space-y-3">
    @csrf
    @if ($method === 'PUT') @method('PUT') @endif

    <div class="grid gap-3 sm:grid-cols-3">
        <input name="name" value="{{ old('name', $rule->name) }}" placeholder="Название (для себя)"
               class="rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-white">
        <select name="type" required class="rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-white">
            @foreach ($ruleTypes as $value => $label)
            <option value="{{ $value }}" @selected(old('type', $rule->type) === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <input type="number" name="priority" value="{{ old('priority', $rule->priority) }}" min="1" max="999" required
               class="rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-white" placeholder="Приоритет">
    </div>

    <textarea name="instruction" rows="3" required placeholder="Текст правила для ИИ — что говорить или чего избегать"
              class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-white">{{ old('instruction', $rule->instruction) }}</textarea>

    <div class="rounded-lg border border-slate-700/80 bg-slate-800/40 p-3 space-y-2">
        <p class="text-xs font-medium text-slate-400">Когда применять правило</p>
        <label class="flex items-center gap-2 text-sm text-slate-300">
            <input type="checkbox" name="always_apply" value="1" @checked(old('always_apply', $rule->isAlwaysOn())) class="rounded border-slate-600 text-emerald-500">
            Всегда (в каждом ответе) — для фактов о компании и жёстких запретов
        </label>
        <input name="trigger_keywords_raw"
               value="{{ old('trigger_keywords_raw', implode(', ', $rule->triggerKeywords())) }}"
               placeholder="Или только при словах в сообщении: лизинг, аренда, leasing"
               class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-white">
    </div>

    <div class="flex items-center gap-4">
        <label class="flex items-center gap-2 text-sm text-slate-300">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $rule->is_active ?? true)) class="rounded border-slate-600 text-emerald-500">
            Активно
        </label>
        <button type="submit" class="rounded-lg bg-emerald-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-emerald-500">
            {{ $submitLabel ?? ($method === 'PUT' ? 'Сохранить изменения' : 'Создать правило') }}
        </button>
    </div>
</form>
