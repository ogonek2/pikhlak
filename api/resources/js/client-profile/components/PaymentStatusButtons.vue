<script setup>
import { inject } from 'vue';

const props = defineProps({
    payment: { type: Object, required: true },
    disabled: { type: Boolean, default: false },
});

const { config, updatePayment, markPaymentPaid } = inject('profile');

const statuses = ['pending', 'paid', 'overdue', 'cancelled'];

const shortLabels = {
    pending: 'Ждёт',
    paid: 'Оплачен',
    overdue: 'Проср.',
    cancelled: 'Отмена',
};

function btnClass(status) {
    const active = props.payment.status === status;
    const base = 'rounded-md px-2 py-1 text-[10px] font-medium transition disabled:opacity-40';

    if (!active) {
        return `${base} text-slate-500 hover:bg-slate-800 hover:text-slate-300`;
    }

    const map = {
        pending: 'bg-slate-700 text-slate-200 ring-1 ring-slate-600',
        paid: 'bg-emerald-600/90 text-white ring-1 ring-emerald-500/50',
        overdue: 'bg-red-600/90 text-white ring-1 ring-red-500/50',
        cancelled: 'bg-slate-800 text-slate-400 ring-1 ring-slate-600 line-through',
    };

    return `${base} ${map[status] ?? map.pending}`;
}

async function setStatus(status) {
    if (props.disabled || props.payment.status === status) {
        return;
    }

    if (status === 'paid') {
        await markPaymentPaid(props.payment.id);
        return;
    }

    await updatePayment(props.payment.id, {
        type: props.payment.type,
        amount: props.payment.amount,
        due_date: props.payment.due_date,
        status,
        paid_at: null,
        notes: props.payment.notes ?? '',
    });
}
</script>

<template>
    <div class="inline-flex flex-wrap gap-0.5 rounded-lg border border-slate-800 bg-slate-950/50 p-0.5">
        <button
            v-for="status in statuses"
            :key="status"
            type="button"
            :class="btnClass(status)"
            :disabled="disabled"
            :title="config.paymentStatuses[status]"
            @click="setStatus(status)"
        >
            {{ shortLabels[status] ?? config.paymentStatuses[status] }}
        </button>
    </div>
</template>
