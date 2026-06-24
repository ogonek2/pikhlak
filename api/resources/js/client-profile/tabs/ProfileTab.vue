<script setup>
import { inject, reactive } from 'vue';
import { btnPrimary, inputClass, labelClass } from '../styles';

const profile = inject('profile');
const { client, config, updateProfile } = profile;

const form = reactive({
    full_name: client.full_name,
    email: client.email ?? '',
    status: client.status,
    telegram_chat_id: client.telegram_chat_id ?? '',
    telegram_user_id: client.telegram_user_id ?? '',
    notifications_enabled: client.notifications_enabled,
    notes: client.notes ?? '',
});

async function submit() {
    await updateProfile({
        ...form,
        telegram_chat_id: form.telegram_chat_id || client.telegram_chat_id || null,
        telegram_user_id: form.telegram_user_id || client.telegram_user_id || null,
        notifications_enabled: form.notifications_enabled ? 1 : 0,
    });
    Object.assign(form, {
        full_name: client.full_name,
        email: client.email ?? '',
        status: client.status,
        telegram_chat_id: client.telegram_chat_id ?? '',
        telegram_user_id: client.telegram_user_id ?? '',
        notifications_enabled: client.notifications_enabled,
        notes: client.notes ?? '',
    });
}

function fieldError(name) {
    return profile.errors[name]?.[0];
}
</script>

<template>
    <form class="grid gap-4 sm:grid-cols-2" @submit.prevent="submit">
        <div class="sm:col-span-2">
            <label :class="labelClass">ФИО</label>
            <input v-model="form.full_name" required :class="inputClass">
            <p v-if="fieldError('full_name')" class="mt-1 text-xs text-red-400">{{ fieldError('full_name') }}</p>
        </div>
        <div>
            <label :class="labelClass">Email</label>
            <input v-model="form.email" type="email" :class="inputClass">
        </div>
        <div>
            <label :class="labelClass">Статус</label>
            <select v-model="form.status" :class="inputClass">
                <option v-for="(lbl, key) in config.statuses" :key="key" :value="key">{{ lbl }}</option>
            </select>
        </div>
        <div>
            <label :class="labelClass">Telegram chat ID</label>
            <input v-model="form.telegram_chat_id" :class="[inputClass, 'font-mono text-xs']">
        </div>
        <div>
            <label :class="labelClass">Telegram user ID</label>
            <input v-model="form.telegram_user_id" :class="[inputClass, 'font-mono text-xs']">
        </div>
        <div class="sm:col-span-2">
            <label class="flex items-center gap-2 text-sm text-slate-300">
                <input v-model="form.notifications_enabled" type="checkbox" class="rounded border-slate-600 bg-slate-800 text-sky-500">
                Уведомления в Telegram
            </label>
        </div>
        <div class="sm:col-span-2">
            <label :class="labelClass">Заметки</label>
            <textarea v-model="form.notes" rows="4" :class="inputClass" />
        </div>
        <div class="sm:col-span-2 pt-2">
            <button type="submit" :class="btnPrimary">Сохранить</button>
        </div>
    </form>
</template>
