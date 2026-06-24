<script setup>
import { computed } from 'vue';

const props = defineProps({
    segments: { type: Array, default: () => [] },
    stats: { type: Object, default: () => ({}) },
});

const progressPercent = computed(() => {
    const total = props.stats.totalAmount ?? 0;
    const paid = props.stats.paidAmount ?? 0;
    if (total <= 0) {
        return 0;
    }

    return Math.min(100, Math.round((paid / total) * 100));
});

function segmentClass(status) {
    if (status === 'paid') {
        return 'bg-emerald-500 border-emerald-400/60';
    }
    if (status === 'overdue') {
        return 'bg-red-500 border-red-400/60';
    }
    if (status === 'partial') {
        return 'bg-gradient-to-r from-emerald-500 from-50% to-slate-600 to-50% border-amber-400/40';
    }

    return 'bg-slate-600/80 border-slate-500/50';
}

function segmentTitle(segment) {
    const paid = segment.paidCount ?? 0;
    const total = segment.totalCount ?? 0;
    return `${segment.label}: ${paid}/${total}`;
}
</script>

<template>
    <div class="mb-6 rounded-xl border border-slate-800/80 bg-slate-950/40 p-4">
        <div class="mb-3 flex flex-wrap items-end justify-between gap-2">
            <div>
                <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-500">Линия успеха оплат</h3>
                <p class="mt-0.5 text-sm text-slate-400">
                    Оплачено периодов: <span class="font-medium text-emerald-400">{{ stats.paidPeriods ?? 0 }}</span>
                    <span class="text-slate-600"> / </span>
                    <span class="text-slate-300">{{ stats.totalPeriods ?? 0 }}</span>
                    <span v-if="(stats.overduePeriods ?? 0) > 0" class="ml-2 text-red-400">
                        · просрочено {{ stats.overduePeriods }}
                    </span>
                </p>
            </div>
            <div class="text-right text-sm">
                <span class="text-slate-500">Прогресс</span>
                <span class="ml-2 font-semibold text-white">{{ progressPercent }}%</span>
            </div>
        </div>

        <div v-if="!segments.length" class="py-4 text-center text-sm text-slate-600">
            Нет платежей для отображения
        </div>

        <template v-else>
            <div class="flex gap-0.5 overflow-x-auto pb-1">
                <div
                    v-for="segment in segments"
                    :key="segment.id"
                    :title="segmentTitle(segment)"
                    class="group relative min-w-[28px] flex-1 rounded-md border transition hover:scale-y-110 hover:z-10"
                    :class="[segmentClass(segment.status), segment.kind === 'down' ? 'max-w-[48px]' : '']"
                    style="height: 14px"
                >
                    <span
                        v-if="segment.status === 'paid'"
                        class="absolute -top-5 left-1/2 -translate-x-1/2 text-[10px] text-emerald-400 opacity-0 transition group-hover:opacity-100"
                    >✓</span>
                </div>
            </div>

            <div class="mt-3 flex flex-wrap gap-4 text-[10px] uppercase tracking-wide text-slate-500">
                <span class="inline-flex items-center gap-1.5">
                    <span class="h-2 w-4 rounded-sm bg-emerald-500" /> Оплачено
                </span>
                <span class="inline-flex items-center gap-1.5">
                    <span class="h-2 w-4 rounded-sm bg-slate-600" /> Ожидает
                </span>
                <span class="inline-flex items-center gap-1.5">
                    <span class="h-2 w-4 rounded-sm bg-red-500" /> Просрочено
                </span>
                <span class="inline-flex items-center gap-1.5">
                    <span class="h-2 w-4 rounded-sm bg-gradient-to-r from-emerald-500 to-slate-600" /> Частично
                </span>
            </div>
        </template>
    </div>
</template>
