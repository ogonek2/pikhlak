<div class="space-y-2">
    @forelse ($client->phones as $phone)
        <div class="group flex items-center justify-between rounded-xl border border-slate-800/80 bg-slate-950/40 px-4 py-3 text-sm transition hover:border-slate-700">
            <div>
                <span class="text-xs text-slate-500">{{ $phone->label }}</span>
                <span class="ml-2 font-medium">{{ $phone->phone }}</span>
                @if ($phone->is_primary)<span class="ml-2 rounded bg-sky-500/10 px-1.5 py-0.5 text-[10px] text-sky-400">основной</span>@endif
            </div>
            <form method="POST" action="{{ route('admin.client.clients.phones.destroy', [$client, $phone]) }}" onsubmit="return confirm('Удалить?')">
                @csrf @method('DELETE')
                <button type="submit" class="text-xs text-slate-600 opacity-0 transition group-hover:opacity-100 hover:text-red-400">Удалить</button>
            </form>
        </div>
    @empty
        <p class="py-6 text-center text-sm text-slate-600">Телефонов пока нет</p>
    @endforelse
</div>
<form method="POST" action="{{ route('admin.client.clients.phones.store', $client) }}" class="mt-6 grid gap-3 border-t border-slate-800/80 pt-5 sm:grid-cols-3">
    @csrf
    <div><label class="{{ $label }}">Метка</label><input name="label" value="mobile" class="{{ $input }}"></div>
    <div><label class="{{ $label }}">Номер</label><input name="phone" required placeholder="+380..." class="{{ $input }}"></div>
    <div class="flex items-end gap-3">
        <label class="flex items-center gap-2 pb-2 text-xs text-slate-400"><input type="checkbox" name="is_primary" value="1" class="rounded border-slate-600 bg-slate-800 text-sky-500"> Основной</label>
        <button type="submit" class="rounded-lg border border-slate-700 px-4 py-2 text-sm text-slate-200 hover:bg-slate-800">Добавить</button>
    </div>
</form>
