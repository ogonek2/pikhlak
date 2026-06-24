<script setup>
import { inject, reactive, ref } from 'vue';
import { formatDate, formatMoney } from '../format';
import { btnPrimary, dashedFormClass, inputClass, labelClass } from '../styles';

const { client, config, addContract, updateContract, removeContract } = inject('profile');

const editingId = ref(null);
const today = new Date().toISOString().slice(0, 10);

const empty = () => ({
    contract_number: '',
    rental_client_vehicle_id: '',
    status: 'active',
    rent_start: today,
    rent_end: '',
    monthly_amount: '',
    total_amount: '',
    currency: 'UAH',
    buyout_option: true,
    notes: '',
});

const form = reactive(empty());

function startEdit(c) {
    editingId.value = c.id;
    Object.assign(form, {
        contract_number: c.contract_number ?? '',
        rental_client_vehicle_id: c.rental_client_vehicle_id ?? '',
        status: c.status,
        rent_start: c.rent_start,
        rent_end: c.rent_end ?? '',
        monthly_amount: c.monthly_amount,
        total_amount: c.total_amount ?? '',
        currency: c.currency,
        buyout_option: c.buyout_option,
        notes: c.notes ?? '',
    });
}

function cancelEdit() {
    editingId.value = null;
    Object.assign(form, empty());
}

function payload() {
    return {
        ...form,
        rental_client_vehicle_id: form.rental_client_vehicle_id || null,
        rent_end: form.rent_end || null,
        total_amount: form.total_amount || null,
        buyout_option: form.buyout_option ? 1 : 0,
    };
}

async function submitCreate() {
    await addContract(payload());
    Object.assign(form, empty());
}

async function submitUpdate() {
    await updateContract(editingId.value, payload());
    cancelEdit();
}

async function destroy(id) {
    if (!confirm('Удалить?')) return;
    if (editingId.value === id) cancelEdit();
    await removeContract(id);
}
</script>

<template>
    <div>
    <div v-for="c in client.contracts" :key="c.id" class="group mb-3 rounded-xl border border-slate-800/80 bg-slate-950/40 px-4 py-3 text-sm">
        <div class="flex items-start justify-between">
            <div>
                <div v-if="c.contract_number" class="font-mono text-xs text-sky-400">№ {{ c.contract_number }}</div>
                <div class="mt-1 font-medium">
                    {{ formatMoney(c.monthly_amount, c.currency, config.currencySymbols) }}
                    <span class="font-normal text-slate-500">/ {{ c.period_weeks ?? 4 }} нед.</span>
                </div>
                <div v-if="c.weekly_amount" class="text-xs text-slate-600">
                    ≈ {{ formatMoney(c.weekly_amount, c.currency, config.currencySymbols) }} / нед.
                </div>
                <div class="mt-0.5 text-xs text-slate-500">
                    {{ formatDate(c.rent_start) }} — {{ c.rent_end ? formatDate(c.rent_end) : '∞' }}
                </div>
            </div>
            <div class="flex gap-3 text-xs opacity-0 transition group-hover:opacity-100">
                <button type="button" class="text-sky-400" @click="startEdit(c)">Изменить</button>
                <button type="button" class="text-red-400" @click="destroy(c.id)">Удалить</button>
            </div>
        </div>
    </div>

    <div :class="dashedFormClass">
        <h3 class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-500">
            {{ editingId ? 'Редактирование' : 'Новый договор' }}
        </h3>
        <form class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3" @submit.prevent="editingId ? submitUpdate() : submitCreate()">
            <div><label :class="labelClass">Номер договора</label><input v-model="form.contract_number" :class="[inputClass, 'font-mono']" placeholder="PK-2024-001"></div>
            <div>
                <label :class="labelClass">Автомобиль</label>
                <select v-model="form.rental_client_vehicle_id" :class="inputClass">
                    <option value="">—</option>
                    <option v-for="veh in client.vehicles" :key="veh.id" :value="veh.id">{{ veh.title }}</option>
                </select>
            </div>
            <div>
                <label :class="labelClass">Статус</label>
                <select v-model="form.status" :class="inputClass">
                    <option v-for="(lbl, key) in config.contractStatuses" :key="key" :value="key">{{ lbl }}</option>
                </select>
            </div>
            <div><label :class="labelClass">Начало</label><input v-model="form.rent_start" type="date" required :class="inputClass"></div>
            <div><label :class="labelClass">Окончание</label><input v-model="form.rent_end" type="date" :class="inputClass"></div>
            <div><label :class="labelClass">Платёж / 4 нед.</label><input v-model="form.monthly_amount" type="number" step="0.01" required :class="inputClass"></div>
            <div><label :class="labelClass">Сумма договора</label><input v-model="form.total_amount" type="number" step="0.01" :class="inputClass"></div>
            <div>
                <label :class="labelClass">Валюта</label>
                <select v-model="form.currency" :class="inputClass">
                    <option v-for="cur in ['UAH', 'USD', 'EUR']" :key="cur" :value="cur">{{ cur }}</option>
                </select>
            </div>
            <div class="flex flex-wrap items-center gap-4 sm:col-span-2">
                <label class="flex items-center gap-2 text-sm">
                    <input v-model="form.buyout_option" type="checkbox" class="rounded border-slate-600 bg-slate-800 text-sky-500">
                    Право выкупа
                </label>
                <button type="submit" :class="btnPrimary">{{ editingId ? 'Сохранить' : 'Добавить' }}</button>
                <button v-if="editingId" type="button" class="text-sm text-slate-500 hover:text-slate-300" @click="cancelEdit">Отмена</button>
            </div>
            <div class="sm:col-span-3"><label :class="labelClass">Примечания</label><textarea v-model="form.notes" rows="2" :class="inputClass" /></div>
        </form>
    </div>
    </div>
</template>
