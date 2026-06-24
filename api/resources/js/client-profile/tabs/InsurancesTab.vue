<script setup>
import { inject, reactive, ref } from 'vue';
import { formatDate } from '../format';
import { btnPrimary, dashedFormClass, inputClass, labelClass } from '../styles';

const { client, addInsurance, updateInsurance, removeInsurance } = inject('profile');

const editingId = ref(null);

const empty = () => ({
    provider: '',
    policy_number: '',
    premium_amount: '',
    valid_from: '',
    valid_until: '',
    coverage_notes: '',
});

const form = reactive(empty());

function startEdit(ins) {
    editingId.value = ins.id;
    Object.assign(form, {
        provider: ins.provider,
        policy_number: ins.policy_number ?? '',
        premium_amount: ins.premium_amount ?? '',
        valid_from: ins.valid_from ?? '',
        valid_until: ins.valid_until ?? '',
        coverage_notes: ins.coverage_notes ?? '',
    });
}

function cancelEdit() {
    editingId.value = null;
    Object.assign(form, empty());
}

function payload() {
    return {
        ...form,
        premium_amount: form.premium_amount || null,
        valid_from: form.valid_from || null,
        valid_until: form.valid_until || null,
    };
}

async function submitCreate() {
    await addInsurance(payload());
    Object.assign(form, empty());
}

async function submitUpdate() {
    await updateInsurance(editingId.value, payload());
    cancelEdit();
}

async function destroy(id) {
    if (!confirm('Удалить?')) return;
    if (editingId.value === id) cancelEdit();
    await removeInsurance(id);
}
</script>

<template>
    <div>
    <div v-for="ins in client.insurances" :key="ins.id" class="group mb-3 flex justify-between rounded-xl border border-slate-800/80 bg-slate-950/40 px-4 py-3 text-sm">
        <div>
            <div class="font-medium">{{ ins.provider }}</div>
            <div class="mt-0.5 text-xs text-slate-500">
                {{ formatDate(ins.valid_from) }} — {{ formatDate(ins.valid_until) }}
            </div>
        </div>
        <div class="flex gap-3 text-xs opacity-0 transition group-hover:opacity-100">
            <button type="button" class="text-sky-400" @click="startEdit(ins)">Изменить</button>
            <button type="button" class="text-red-400" @click="destroy(ins.id)">Удалить</button>
        </div>
    </div>

    <div :class="dashedFormClass">
        <h3 class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-500">
            {{ editingId ? 'Редактирование' : 'Новая страховка' }}
        </h3>
        <form class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3" @submit.prevent="editingId ? submitUpdate() : submitCreate()">
            <div><label :class="labelClass">Страховая</label><input v-model="form.provider" required :class="inputClass"></div>
            <div><label :class="labelClass">Номер полиса</label><input v-model="form.policy_number" :class="inputClass"></div>
            <div><label :class="labelClass">Премия</label><input v-model="form.premium_amount" type="number" step="0.01" :class="inputClass"></div>
            <div><label :class="labelClass">Действует с</label><input v-model="form.valid_from" type="date" :class="inputClass"></div>
            <div><label :class="labelClass">Действует до</label><input v-model="form.valid_until" type="date" :class="inputClass"></div>
            <div class="flex items-end gap-3">
                <button type="submit" :class="btnPrimary">{{ editingId ? 'Сохранить' : 'Добавить' }}</button>
                <button v-if="editingId" type="button" class="text-sm text-slate-500 hover:text-slate-300" @click="cancelEdit">Отмена</button>
            </div>
            <div class="sm:col-span-3"><label :class="labelClass">Покрытие / примечания</label><textarea v-model="form.coverage_notes" rows="2" :class="inputClass" /></div>
        </form>
    </div>
    </div>
</template>
