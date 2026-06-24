<script setup>
import { inject, reactive, ref } from 'vue';
import { btnGhost, btnPrimary, dashedFormClass, inputClass, labelClass } from '../styles';

const { client, addVehicle, updateVehicle, removeVehicle } = inject('profile');

const editingId = ref(null);

const empty = () => ({
    make: '',
    model: '',
    year: '',
    plate_number: '',
    vin: '',
    mileage: '',
    is_current: true,
});

const form = reactive(empty());

function startEdit(vehicle) {
    editingId.value = vehicle.id;
    Object.assign(form, {
        make: vehicle.make,
        model: vehicle.model,
        year: vehicle.year ?? '',
        plate_number: vehicle.plate_number ?? '',
        vin: vehicle.vin ?? '',
        mileage: vehicle.mileage ?? '',
        is_current: vehicle.is_current,
    });
}

function cancelEdit() {
    editingId.value = null;
    Object.assign(form, empty());
}

function payload() {
    return {
        ...form,
        year: form.year || null,
        mileage: form.mileage || null,
        is_current: form.is_current ? 1 : 0,
    };
}

async function submitCreate() {
    await addVehicle(payload());
    Object.assign(form, empty());
}

async function submitUpdate() {
    await updateVehicle(editingId.value, payload());
    cancelEdit();
}

async function destroy(id) {
    if (!confirm('Удалить?')) return;
    if (editingId.value === id) cancelEdit();
    await removeVehicle(id);
}
</script>

<template>
    <div>
    <div v-for="v in client.vehicles" :key="v.id" class="group mb-3 flex items-start justify-between rounded-xl border border-slate-800/80 bg-slate-950/40 px-4 py-3 text-sm">
        <div>
            <div class="font-medium">
                {{ v.title }}
                <span v-if="v.is_current" class="text-[10px] text-sky-400">· текущий</span>
            </div>
            <div v-if="v.plate_number" class="mt-0.5 text-slate-500">{{ v.plate_number }}</div>
            <div v-if="v.mileage" class="text-xs text-slate-600">{{ Number(v.mileage).toLocaleString('uk-UA') }} км</div>
        </div>
        <div class="flex gap-3 text-xs opacity-0 transition group-hover:opacity-100">
            <button type="button" class="text-sky-400 hover:underline" @click="startEdit(v)">Изменить</button>
            <button type="button" class="text-red-400 hover:underline" @click="destroy(v.id)">Удалить</button>
        </div>
    </div>

    <div v-if="editingId" :class="dashedFormClass">
        <h3 class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Редактирование</h3>
        <form class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3" @submit.prevent="submitUpdate">
            <div><label :class="labelClass">Марка</label><input v-model="form.make" required :class="inputClass"></div>
            <div><label :class="labelClass">Модель</label><input v-model="form.model" required :class="inputClass"></div>
            <div><label :class="labelClass">Год</label><input v-model="form.year" type="number" :class="inputClass"></div>
            <div><label :class="labelClass">Гос. номер</label><input v-model="form.plate_number" :class="inputClass"></div>
            <div><label :class="labelClass">VIN</label><input v-model="form.vin" :class="[inputClass, 'font-mono text-xs']"></div>
            <div><label :class="labelClass">Пробег, км</label><input v-model="form.mileage" type="number" :class="inputClass"></div>
            <div class="flex flex-wrap items-center gap-4 sm:col-span-2">
                <label class="flex items-center gap-2 text-sm">
                    <input v-model="form.is_current" type="checkbox" class="rounded border-slate-600 bg-slate-800 text-sky-500">
                    Текущий автомобиль
                </label>
                <button type="submit" :class="btnPrimary">Сохранить</button>
                <button type="button" class="text-sm text-slate-500 hover:text-slate-300" @click="cancelEdit">Отмена</button>
            </div>
        </form>
    </div>

    <div v-if="!editingId" :class="dashedFormClass">
        <h3 class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Новый автомобиль</h3>
        <form class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3" @submit.prevent="submitCreate">
            <div><label :class="labelClass">Марка</label><input v-model="form.make" required :class="inputClass"></div>
            <div><label :class="labelClass">Модель</label><input v-model="form.model" required :class="inputClass"></div>
            <div><label :class="labelClass">Год</label><input v-model="form.year" type="number" :class="inputClass"></div>
            <div><label :class="labelClass">Гос. номер</label><input v-model="form.plate_number" :class="inputClass"></div>
            <div><label :class="labelClass">VIN</label><input v-model="form.vin" :class="[inputClass, 'font-mono text-xs']"></div>
            <div><label :class="labelClass">Пробег, км</label><input v-model="form.mileage" type="number" :class="inputClass"></div>
            <div class="flex flex-wrap items-center gap-4 sm:col-span-2">
                <label class="flex items-center gap-2 text-sm">
                    <input v-model="form.is_current" type="checkbox" class="rounded border-slate-600 bg-slate-800 text-sky-500">
                    Текущий автомобиль
                </label>
                <button type="submit" :class="btnPrimary">Добавить</button>
            </div>
        </form>
    </div>
    </div>
</template>
