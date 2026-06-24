<form method="POST" action="{{ route('admin.client.clients.update', $client) }}" class="grid gap-4 sm:grid-cols-2">
    @csrf @method('PUT')
    <div class="sm:col-span-2">
        <label class="{{ $label }}">ФИО</label>
        <input name="full_name" value="{{ old('full_name', $client->full_name) }}" required class="{{ $input }}">
    </div>
    <div>
        <label class="{{ $label }}">Email</label>
        <input name="email" type="email" value="{{ old('email', $client->email) }}" class="{{ $input }}">
    </div>
    <div>
        <label class="{{ $label }}">Статус</label>
        <select name="status" class="{{ $input }}">
            @foreach ($statuses as $key => $lbl)
                <option value="{{ $key }}" @selected(old('status', $client->status) === $key)>{{ $lbl }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="{{ $label }}">Telegram chat ID</label>
        <input name="telegram_chat_id" value="{{ old('telegram_chat_id', $client->telegram_chat_id) }}" class="{{ $input }} font-mono text-xs">
    </div>
    <div>
        <label class="{{ $label }}">Telegram user ID</label>
        <input name="telegram_user_id" value="{{ old('telegram_user_id', $client->telegram_user_id) }}" class="{{ $input }} font-mono text-xs">
    </div>
    <div class="sm:col-span-2">
        <label class="flex items-center gap-2 text-sm text-slate-300">
            <input type="checkbox" name="notifications_enabled" value="1" @checked(old('notifications_enabled', $client->notifications_enabled))
                   class="rounded border-slate-600 bg-slate-800 text-sky-500">
            Уведомления в Telegram
        </label>
    </div>
    <div class="sm:col-span-2">
        <label class="{{ $label }}">Заметки</label>
        <textarea name="notes" rows="4" class="{{ $input }}">{{ old('notes', $client->notes) }}</textarea>
    </div>
    <div class="sm:col-span-2 pt-2">
        <button type="submit" class="rounded-lg bg-sky-600 px-5 py-2 text-sm font-medium text-white hover:bg-sky-500">Сохранить</button>
    </div>
</form>
