<script setup>
import { inject, reactive, ref } from 'vue';
import { formatDate } from '../format';
import { btnPrimary, dashedFormClass, inputClass, labelClass } from '../styles';

const { client, config, addMaintenance, updateMaintenance, removeMaintenance } = inject('profile');

const editingId = ref(null);

const empty = () => ({
    title: '',
    type: 'service',
    status: 'planned',
    rental_client_vehicle_id: '',
    scheduled_at: '',
    completed_at: '',
    mileage_at: '',
    cost: '',
    notes: '',
});

const form = reactive(empty());

function startEdit(m) {
    editingId.value = m.id;
    Object.assign(form, {
        title: m.title,
        type: m.type,
        status: m.status,
        rental_client_vehicle_id: m.rental_client_vehicle_id ?? '',
        scheduled_at: m.scheduled_at ?? '',
        completed_at: m.completed_at ?? '',
        mileage_at: m.mileage_at ?? '',
        cost: m.cost ?? '',
        notes: m.notes ?? '',
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
        scheduled_at: form.scheduled_at || null,
        completed_at: form.completed_at || null,
        mileage_at: form.mileage_at || null,
        cost: form.cost || null,
    };
}

async function submitCreate() {
    await addMaintenance(payload());
    Object.assign(form, empty());
}

async function submitUpdate() {
    await updateMaintenance(editingId.value, payload());
    cancelEdit();
}

async function destroy(id) {
    if (!confirm('Удалить?')) return;
    if (editingId.value === id) cancelEdit();
    await removeMaintenance(id);
}
</script>

<template>
    <div>
    <div v-for="m in client.maintenances" :key="m.id" class="group mb-3 flex justify-between rounded-xl border border-slate-800/80 bg-slate-950/40 px-4 py-3 text-sm">
        <div>
            <div class="font-medium">{{ m.title }}</div>
            <div class="mt-0.5 text-xs text-slate-500">
                {{ config.maintenanceTypes[m.type] ?? m.type }} · {{ formatDate(m.scheduled_at) }}
            </div>
        </div>
        <div class="flex gap-3 text-xs opacity-0 transition group-hover:opacity-100">
            <button type="button" class="text-sky-400" @click="startEdit(m)">Изменить</button>
            <button type="button" class="text-red-400" @click="destroy(m.id)">Удалить</button>
        </div>
    </div>

    <div :class="dashedFormClass">
        <h3 class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-500">
            {{ editingId ? 'Редактирование' : 'Новая запись' }}
        </h3>
        <form class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3" @submit.prevent="editingId ? submitUpdate() : submitCreate()">
            <div><label :class="labelClass">Название</label><input v-model="form.title" required placeholder="Плановое ТО" :class="inputClass"></div>
            <div>
                <label :class="labelClass">Тип</label>
                <select v-model="form.type" :class="inputClass">
                    <option v-for="(lbl, key) in config.maintenanceTypes" :key="key" :value="key">{{ lbl }}</option>
                </select>
            </div>
            <div>
                <label :class="labelClass">Статус</label>
                <select v-model="form.status" :class="inputClass">
                    <option v-for="(lbl, key) in config.maintenanceStatuses" :key="key" :value="key">{{ lbl }}</option>
                </select>
            </div>
            <div>
                <label :class="labelClass">Автомобиль</label>
                <select v-model="form.rental_client_vehicle_id" :class="inputClass">
                    <option value="">—</option>
                    <option v-for="veh in client.vehicles" :key="veh.id" :value="veh.id">{{ veh.title }}</option>
                </select>
            </div>
            <div><label :class="labelClass">Запланировано</label><input v-model="form.scheduled_at" type="date" :class="inputClass"></div>
            <div><label :class="labelClass">Выполнено</label><input v-model="form.completed_at" type="date" :class="inputClass"></div>
            <div><label :class="labelClass">Пробег</label><input v-model="form.mileage_at" type="number" :class="inputClass"></div>
            <div><label :class="labelClass">Стоимость</label><input v-model="form.cost" type="number" step="0.01" :class="inputClass"></div>
            <div class="flex items-end gap-3">
                <button type="submit" :class="btnPrimary">{{ editingId ? 'Сохранить' : 'Добавить' }}</button>
                <button v-if="editingId" type="button" class="text-sm text-slate-500 hover:text-slate-300" @click="cancelEdit">Отмена</button>
            </div>
            <div class="sm:col-span-3"><label :class="labelClass">Примечания</label><textarea v-model="form.notes" rows="2" :class="inputClass" /></div>
        </form>
    </div>
    </div>
</template>
