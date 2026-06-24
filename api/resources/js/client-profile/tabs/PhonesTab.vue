<script setup>
import { inject, reactive } from 'vue';
import { btnGhost, cardClass, inputClass, labelClass } from '../styles';

const { client, addPhone, removePhone } = inject('profile');

const form = reactive({ label: 'mobile', phone: '', is_primary: false });

async function submit() {
    await addPhone({
        label: form.label,
        phone: form.phone,
        is_primary: form.is_primary ? 1 : 0,
    });
    form.label = 'mobile';
    form.phone = '';
    form.is_primary = false;
}

async function destroy(id) {
    if (!confirm('Удалить?')) return;
    await removePhone(id);
}
</script>

<template>
    <div>
        <div class="space-y-2">
        <div v-if="!client.phones.length" class="py-6 text-center text-sm text-slate-600">Телефонов пока нет</div>
        <div v-for="phone in client.phones" :key="phone.id" :class="cardClass">
            <div>
                <span class="text-xs text-slate-500">{{ phone.label }}</span>
                <span class="ml-2 font-medium">{{ phone.phone }}</span>
                <span v-if="phone.is_primary" class="ml-2 rounded bg-sky-500/10 px-1.5 py-0.5 text-[10px] text-sky-400">основной</span>
            </div>
            <button type="button" class="text-xs text-slate-600 opacity-0 transition group-hover:opacity-100 hover:text-red-400" @click="destroy(phone.id)">
                Удалить
            </button>
        </div>
    </div>

    <form class="mt-6 grid gap-3 border-t border-slate-800/80 pt-5 sm:grid-cols-3" @submit.prevent="submit">
        <div>
            <label :class="labelClass">Метка</label>
            <input v-model="form.label" :class="inputClass">
        </div>
        <div>
            <label :class="labelClass">Номер</label>
            <input v-model="form.phone" required placeholder="+380..." :class="inputClass">
        </div>
        <div class="flex items-end gap-3">
            <label class="flex items-center gap-2 pb-2 text-xs text-slate-400">
                <input v-model="form.is_primary" type="checkbox" class="rounded border-slate-600 bg-slate-800 text-sky-500">
                Основной
            </label>
            <button type="submit" :class="btnGhost">Добавить</button>
        </div>
    </form>
    </div>
</template>
