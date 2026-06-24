<script setup>
import { computed, inject, reactive, ref, watch } from 'vue';
import { formatDate, formatMoney } from '../format';
import { groupPayments, periodWeeksFromContract } from '../paymentsGrouping';
import { btnGhost, btnPrimary, inputClass, labelClass } from '../styles';
import PaymentSuccessRoadmap from '../components/PaymentSuccessRoadmap.vue';
import PaymentStatusButtons from '../components/PaymentStatusButtons.vue';
import ProfileModal from '../components/ProfileModal.vue';

const profile = inject('profile');
const { client, config, addPayment, updatePayment, removePayment, markPaymentPaid } = profile;

const viewTab = ref('schedule');
const modalOpen = ref(false);
const editingPayment = ref(null);
const expanded = ref(new Set());
const today = new Date().toISOString().slice(0, 10);

const periodWeeks = computed(() => periodWeeksFromContract(client.contracts));

const currency = computed(() => {
    const contract = client.contracts?.find((c) => c.status === 'active') ?? client.contracts?.[0];
    return contract?.currency ?? 'USD';
});

const grouped = computed(() => groupPayments(client.payments ?? [], periodWeeks.value));
const groups = computed(() => grouped.value.groups);
const roadmap = computed(() => grouped.value.roadmap);
const stats = computed(() => grouped.value.stats);

const emptyPayment = () => ({
    type: 'rent',
    amount: '',
    due_date: today,
    paid_at: '',
    status: 'pending',
    notes: '',
});

const editForm = reactive(emptyPayment());

const periodForm = reactive({
    start_date: today,
    period_amount: '',
    weeks: periodWeeks.value,
});

watch(periodWeeks, (n) => {
    periodForm.weeks = n;
});

const singleForm = reactive(emptyPayment());

function isExpanded(id) {
    return expanded.value.has(id);
}

function toggleGroup(id) {
    if (expanded.value.has(id)) {
        expanded.value.delete(id);
    } else {
        expanded.value.add(id);
    }
}

function pickDefaultGroup() {
    return groups.value.find((g) => g.status === 'overdue' || g.status === 'partial')
        ?? groups.value.find((g) => g.status === 'pending')
        ?? groups.value[0];
}

watch(groups, (list) => {
    if (!list.length || expanded.value.size > 0) {
        return;
    }
    const current = pickDefaultGroup();
    if (current) {
        expanded.value.add(current.id);
    }
}, { immediate: true });

function statusClass(status) {
    if (status === 'overdue') return 'bg-red-500/10 text-red-400 ring-red-500/20';
    if (status === 'paid') return 'bg-emerald-500/10 text-emerald-400 ring-emerald-500/20';
    if (status === 'partial') return 'bg-amber-500/10 text-amber-300 ring-amber-500/20';
    return 'bg-slate-800 text-slate-400 ring-slate-700/50';
}

function statusLabel(status) {
    const map = {
        paid: 'Оплачен',
        pending: 'Ожидает',
        overdue: 'Просрочен',
        partial: 'Частично',
    };
    return map[status] ?? config.paymentStatuses[status] ?? status;
}

function groupSubtitle(group) {
    if (group.kind === 'down') {
        return formatDate(group.dateFrom);
    }
    const weeks = group.weekFrom != null && group.weekTo != null
        ? `нед. ${group.weekFrom}–${group.weekTo}`
        : `${group.weeks.length} нед.`;
    const dates = group.dateFrom && group.dateTo
        ? `${formatDate(group.dateFrom)} — ${formatDate(group.dateTo)}`
        : '';
    return [weeks, dates].filter(Boolean).join(' · ');
}

function openEdit(p) {
    editingPayment.value = p;
    Object.assign(editForm, {
        type: p.type,
        amount: p.amount,
        due_date: p.due_date,
        paid_at: p.paid_at ?? '',
        status: p.status,
        notes: p.notes ?? '',
    });
    modalOpen.value = true;
}

function closeModal() {
    modalOpen.value = false;
    editingPayment.value = null;
    Object.assign(editForm, emptyPayment());
}

function editPayload() {
    return {
        ...editForm,
        paid_at: editForm.paid_at || null,
    };
}

async function submitEdit() {
    if (!editingPayment.value) return;
    await updatePayment(editingPayment.value.id, editPayload());
    closeModal();
}

async function destroy(id) {
    if (!confirm('Удалить платёж?')) return;
    if (editingPayment.value?.id === id) closeModal();
    await removePayment(id);
}

async function markPeriodPaid(group) {
    const unpaid = group.weeks.filter((w) => w.status !== 'paid' && w.status !== 'cancelled');
    if (!unpaid.length) return;
    if (!confirm(`Отметить период «${group.label}» оплаченным (${unpaid.length} платежей)?`)) return;
    for (const p of unpaid) {
        await markPaymentPaid(p.id);
    }
}

function nextWeekMeta() {
    const payments = client.payments ?? [];
    const weekly = payments.filter((p) => p.week_number != null && p.week_number > 0);
    const maxWeek = weekly.length ? Math.max(...weekly.map((p) => p.week_number)) : 0;
    const nextWeek = maxWeek + 1;
    const pw = periodWeeks.value;
    return {
        startWeek: nextWeek,
        periodIndex: Math.ceil(nextWeek / pw),
        periodWeeks: pw,
    };
}

function addDays(iso, days) {
    const d = new Date(`${iso}T12:00:00`);
    d.setDate(d.getDate() + days);
    return d.toISOString().slice(0, 10);
}

async function submitSingle() {
    await addPayment({
        ...singleForm,
        paid_at: singleForm.paid_at || null,
    });
    Object.assign(singleForm, emptyPayment());
    viewTab.value = 'schedule';
}

async function submitPeriod() {
    const amount = Number(periodForm.period_amount);
    if (!amount || amount <= 0) return;

    const { startWeek, periodIndex, periodWeeks: pw } = nextWeekMeta();
    const endWeek = startWeek + periodForm.weeks - 1;

    await addPayment({
        type: 'rent',
        amount,
        due_date: addDays(periodForm.start_date, periodForm.weeks * 7),
        status: 'pending',
        paid_at: null,
        week_number: endWeek,
        period_index: periodIndex,
        notes: `Период ${periodIndex} (нед. ${startWeek}–${endWeek})`,
    });

    periodForm.period_amount = '';
    viewTab.value = 'schedule';
}

const contractPeriodPayment = computed(() => {
    const c = client.contracts?.find((x) => x.status === 'active') ?? client.contracts?.[0];
    return c?.monthly_amount ?? '';
});

watch(contractPeriodPayment, (v) => {
    if (v && !periodForm.period_amount) {
        periodForm.period_amount = v;
    }
}, { immediate: true });
</script>

<template>
    <div>
        <div class="mb-5 flex gap-1 rounded-xl border border-slate-800/80 bg-slate-950/40 p-1">
            <button
                type="button"
                class="flex-1 rounded-lg px-3 py-2 text-sm font-medium transition"
                :class="viewTab === 'schedule' ? 'bg-sky-600 text-white' : 'text-slate-400 hover:text-slate-200'"
                @click="viewTab = 'schedule'"
            >
                График
            </button>
            <button
                type="button"
                class="flex-1 rounded-lg px-3 py-2 text-sm font-medium transition"
                :class="viewTab === 'add' ? 'bg-sky-600 text-white' : 'text-slate-400 hover:text-slate-200'"
                @click="viewTab = 'add'"
            >
                + Добавить
            </button>
        </div>

        <template v-if="viewTab === 'schedule'">
            <PaymentSuccessRoadmap :segments="roadmap" :stats="stats" />

            <div v-if="!groups.length" class="rounded-xl border border-slate-800/80 py-12 text-center text-slate-600">
                Платежей нет — добавьте в вкладке «Добавить»
            </div>

            <div v-else class="space-y-3">
                <div
                    v-for="group in groups"
                    :key="group.id"
                    class="overflow-hidden rounded-xl border border-slate-800/80 bg-slate-950/20"
                >
                    <button
                        type="button"
                        class="flex w-full items-center gap-3 px-4 py-3 text-left transition hover:bg-slate-950/40"
                        @click="toggleGroup(group.id)"
                    >
                        <span
                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-sm"
                            :class="{
                                'bg-emerald-500/15 text-emerald-400': group.status === 'paid',
                                'bg-red-500/15 text-red-400': group.status === 'overdue',
                                'bg-amber-500/15 text-amber-300': group.status === 'partial',
                                'bg-slate-800 text-slate-500': group.status === 'pending',
                            }"
                        >
                            {{ group.status === 'paid' ? '✓' : group.periodIndex || '↓' }}
                        </span>

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="font-medium text-slate-200">{{ group.label }}</span>
                                <span class="rounded-full px-2 py-0.5 text-[10px] ring-1" :class="statusClass(group.status)">
                                    {{ statusLabel(group.status) }}
                                </span>
                            </div>
                            <p class="mt-0.5 text-xs text-slate-500">{{ groupSubtitle(group) }}</p>
                        </div>

                        <div class="shrink-0 text-right">
                            <div class="font-semibold text-white">
                                {{ formatMoney(group.total, currency, config.currencySymbols) }}
                            </div>
                        <div v-if="group.kind === 'period'" class="text-[10px] text-slate-500">
                            за {{ group.expectedWeeks }} нед.
                        </div>
                        </div>

                        <span class="shrink-0 text-slate-600 transition" :class="isExpanded(group.id) ? 'rotate-180' : ''">▼</span>
                    </button>

                    <div v-show="isExpanded(group.id)" class="border-t border-slate-800/60">
                        <div
                            v-if="group.kind === 'period' && group.status !== 'paid'"
                            class="flex justify-end border-b border-slate-800/40 bg-slate-950/30 px-4 py-2"
                        >
                            <button
                                type="button"
                                class="text-xs text-emerald-400 hover:underline"
                                :disabled="profile.loading"
                                @click.stop="markPeriodPaid(group)"
                            >
                                ✓ Оплатить весь период
                            </button>
                        </div>

                        <div class="divide-y divide-slate-800/40">
                            <div
                                v-for="p in group.weeks"
                                :key="p.id"
                                class="flex flex-col gap-2 px-4 py-3 transition hover:bg-slate-950/30 sm:flex-row sm:items-center sm:gap-4"
                            >
                                <div class="flex min-w-0 flex-1 items-center gap-3">
                                    <span class="w-10 shrink-0 text-xs text-slate-500">
                                        {{ p.week_number != null ? (p.week_number === 0 ? 'взнос' : `н${p.week_number}`) : '—' }}
                                    </span>
                                    <div class="min-w-0">
                                        <div class="text-sm text-slate-200">{{ formatDate(p.due_date) }}</div>
                                        <div class="text-xs font-medium text-sky-300/90">
                                            {{ Number(p.amount).toLocaleString('uk-UA', { maximumFractionDigits: 2 }) }}
                                            {{ config.currencySymbols[currency] ?? currency }}
                                        </div>
                                    </div>
                                </div>

                                <div class="flex flex-wrap items-center gap-2 sm:justify-end">
                                    <PaymentStatusButtons :payment="p" :disabled="profile.loading" />
                                    <button
                                        type="button"
                                        class="rounded-lg px-2 py-1 text-xs text-sky-400 hover:bg-sky-500/10"
                                        @click="openEdit(p)"
                                    >
                                        Изм.
                                    </button>
                                    <button
                                        type="button"
                                        class="rounded-lg px-2 py-1 text-xs text-red-400/70 hover:bg-red-500/10 hover:text-red-400"
                                        @click="destroy(p.id)"
                                    >
                                        ×
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div
                            v-if="group.kind === 'period' && group.weeks.length > 1"
                            class="flex items-center justify-between border-t border-slate-800/60 bg-slate-950/40 px-4 py-2.5 text-xs"
                        >
                            <span class="uppercase tracking-wide text-slate-500">Итого за {{ group.expectedWeeks }} нед.</span>
                            <span class="font-semibold text-sky-300">
                                {{ formatMoney(group.total, currency, config.currencySymbols) }}
                                <span class="ml-2 font-normal text-slate-500">
                                    (оплачено {{ formatMoney(group.paidTotal, currency, config.currencySymbols) }})
                                </span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <template v-else>
            <div class="space-y-6">
                <section class="rounded-xl border border-slate-800/80 bg-slate-950/30 p-4">
                    <h3 class="mb-1 text-sm font-semibold text-slate-200">Период ({{ periodWeeks }} недели)</h3>
                    <p class="mb-4 text-xs text-slate-500">Один платёж за блок {{ periodWeeks }} недель (как в калькуляторе).</p>
                    <form class="grid gap-3 sm:grid-cols-2" @submit.prevent="submitPeriod">
                        <div>
                            <label :class="labelClass">Дата 1-й недели</label>
                            <input v-model="periodForm.start_date" type="date" required :class="inputClass">
                        </div>
                        <div>
                            <label :class="labelClass">Сумма за {{ periodWeeks }} нед.</label>
                            <input v-model="periodForm.period_amount" type="number" step="0.01" min="0" required :class="inputClass">
                        </div>
                        <div>
                            <label :class="labelClass">Недель в периоде</label>
                            <input v-model.number="periodForm.weeks" type="number" min="1" max="12" required :class="inputClass">
                        </div>
                        <div class="flex items-end">
                            <button type="submit" :class="[btnPrimary, 'w-full sm:w-auto']" :disabled="profile.loading">
                                Добавить период
                            </button>
                        </div>
                    </form>
                </section>

                <section class="rounded-xl border border-dashed border-slate-700/60 bg-slate-950/20 p-4">
                    <h3 class="mb-1 text-sm font-semibold text-slate-200">Один платёж</h3>
                    <p class="mb-4 text-xs text-slate-500">Разовый платёж вне периода или корректировка.</p>
                    <form class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3" @submit.prevent="submitSingle">
                        <div>
                            <label :class="labelClass">Тип</label>
                            <select v-model="singleForm.type" :class="inputClass">
                                <option v-for="(lbl, key) in config.paymentTypes" :key="key" :value="key">{{ lbl }}</option>
                            </select>
                        </div>
                        <div>
                            <label :class="labelClass">Сумма</label>
                            <input v-model="singleForm.amount" type="number" step="0.01" required :class="inputClass">
                        </div>
                        <div>
                            <label :class="labelClass">Срок оплаты</label>
                            <input v-model="singleForm.due_date" type="date" required :class="inputClass">
                        </div>
                        <div>
                            <label :class="labelClass">Статус</label>
                            <select v-model="singleForm.status" :class="inputClass">
                                <option v-for="(lbl, key) in config.paymentStatuses" :key="key" :value="key">{{ lbl }}</option>
                            </select>
                        </div>
                        <div class="sm:col-span-2">
                            <label :class="labelClass">Примечание</label>
                            <input v-model="singleForm.notes" :class="inputClass">
                        </div>
                        <div class="flex items-end">
                            <button type="submit" :class="btnGhost" :disabled="profile.loading">Добавить</button>
                        </div>
                    </form>
                </section>
            </div>
        </template>

        <ProfileModal
            v-model="modalOpen"
            title="Редактирование платежа"
            @close="closeModal"
        >
            <form id="payment-edit-form" class="grid gap-3 sm:grid-cols-2" @submit.prevent="submitEdit">
                <div>
                    <label :class="labelClass">Тип</label>
                    <select v-model="editForm.type" :class="inputClass">
                        <option v-for="(lbl, key) in config.paymentTypes" :key="key" :value="key">{{ lbl }}</option>
                    </select>
                </div>
                <div>
                    <label :class="labelClass">Сумма</label>
                    <input v-model="editForm.amount" type="number" step="0.01" required :class="inputClass">
                </div>
                <div>
                    <label :class="labelClass">Срок оплаты</label>
                    <input v-model="editForm.due_date" type="date" required :class="inputClass">
                </div>
                <div>
                    <label :class="labelClass">Статус</label>
                    <select v-model="editForm.status" :class="inputClass">
                        <option v-for="(lbl, key) in config.paymentStatuses" :key="key" :value="key">{{ lbl }}</option>
                    </select>
                </div>
                <div>
                    <label :class="labelClass">Дата оплаты</label>
                    <input v-model="editForm.paid_at" type="date" :class="inputClass">
                </div>
                <div class="sm:col-span-2">
                    <label :class="labelClass">Примечание</label>
                    <input v-model="editForm.notes" :class="inputClass">
                </div>
            </form>

            <template #footer>
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <button
                        v-if="editingPayment"
                        type="button"
                        class="text-sm text-red-400/80 hover:text-red-400"
                        @click="destroy(editingPayment.id)"
                    >
                        Удалить
                    </button>
                    <div class="ml-auto flex gap-2">
                        <button type="button" :class="btnGhost" @click="closeModal">Отмена</button>
                        <button type="submit" form="payment-edit-form" :class="btnPrimary" :disabled="profile.loading">
                            Сохранить
                        </button>
                    </div>
                </div>
            </template>
        </ProfileModal>
    </div>
</template>
