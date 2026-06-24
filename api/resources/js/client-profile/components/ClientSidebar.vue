<script setup>
import { computed, inject } from 'vue';
import { formatDate, formatMoney, initials, primaryPhone } from '../format';

const profile = inject('profile');
const { client, summary, config, claimTelegram } = profile;

const telegramStatus = computed(() => summary.telegram?.status ?? (client.telegram_chat_id || client.telegram_user_id ? 'linked' : 'none'));

async function onClaimTelegram() {
    if (!confirm('Перенести привязку Telegram на эту карточку клиента?')) {
        return;
    }
    await claimTelegram();
}
</script>

<template>
    <aside class="hidden w-64 shrink-0 xl:block">
        <div class="sticky top-6 space-y-4">
            <div class="overflow-hidden rounded-2xl border border-slate-800/80 bg-gradient-to-b from-slate-900 to-slate-950">
                <div class="border-b border-slate-800/80 px-5 py-6 text-center">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-sky-500/15 text-xl font-semibold text-sky-300 ring-1 ring-sky-500/30">
                        {{ initials(client.full_name) }}
                    </div>
                    <h2 class="mt-3 text-base font-semibold leading-tight">{{ client.full_name }}</h2>
                    <p class="mt-1 text-xs text-slate-500">{{ config.statuses[client.status] ?? client.status }}</p>
                </div>

                <dl class="space-y-3 px-5 py-4 text-sm">
                    <div v-if="primaryPhone(client.phones)">
                        <dt class="text-[10px] font-medium uppercase tracking-wider text-slate-600">Телефон</dt>
                        <dd class="mt-0.5 font-medium">{{ primaryPhone(client.phones).phone }}</dd>
                    </div>
                    <div v-if="summary.vehicle">
                        <dt class="text-[10px] font-medium uppercase tracking-wider text-slate-600">Авто</dt>
                        <dd class="mt-0.5">{{ summary.vehicle.title }}</dd>
                        <dd v-if="summary.vehicle.plate_number" class="text-xs text-slate-500">{{ summary.vehicle.plate_number }}</dd>
                    </div>
                    <div v-if="summary.contract">
                        <dt class="text-[10px] font-medium uppercase tracking-wider text-slate-600">Договор</dt>
                        <dd class="mt-0.5 font-mono text-xs text-sky-400">{{ summary.contract.contract_number || 'без номера' }}</dd>
                        <dd class="text-xs text-slate-400">
                            {{ formatMoney(summary.contract.monthly_amount, summary.contract.currency, config.currencySymbols) }}/4 нед.
                        </dd>
                    </div>
                </dl>
            </div>

            <div class="rounded-2xl border border-slate-800/80 bg-slate-900/50 p-4">
                <h3 class="text-[10px] font-semibold uppercase tracking-wider text-slate-500">Ближайшее</h3>
                <div class="mt-3 space-y-3 text-sm">
                    <div v-if="summary.nextPayment" class="rounded-lg bg-slate-950/60 px-3 py-2">
                        <div class="text-xs text-slate-500">Платёж</div>
                        <div class="font-medium" :class="summary.nextPayment.status === 'overdue' ? 'text-red-400' : 'text-white'">
                            {{ formatMoney(summary.nextPayment.amount) }} · {{ formatDate(summary.nextPayment.due_date) }}
                        </div>
                    </div>
                    <p v-else class="text-xs text-slate-600">Нет ожидающих платежей</p>
                    <div v-if="summary.nextMaintenance" class="rounded-lg bg-slate-950/60 px-3 py-2">
                        <div class="text-xs text-slate-500">ТО / сервис</div>
                        <div class="font-medium">{{ summary.nextMaintenance.title }}</div>
                        <div class="text-xs text-slate-400">{{ formatDate(summary.nextMaintenance.scheduled_at) }}</div>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-800/80 bg-slate-900/50 p-4 text-xs">
                <h3 class="text-[10px] font-semibold uppercase tracking-wider text-slate-500">Статус</h3>
                <ul class="mt-3 space-y-2">
                    <li class="flex items-center justify-between gap-2">
                        <span class="text-slate-500">Telegram</span>
                        <span
                            v-if="telegramStatus === 'linked'"
                            class="text-emerald-400"
                        >Привязан</span>
                        <span
                            v-else-if="telegramStatus === 'sibling'"
                            class="text-amber-400"
                        >На др. карточке</span>
                        <span v-else class="text-amber-400">Нет</span>
                    </li>
                    <li class="flex items-center justify-between">
                        <span class="text-slate-500">Уведомления</span>
                        <span :class="client.notifications_enabled ? 'text-sky-400' : 'text-slate-600'">
                            {{ client.notifications_enabled ? 'Вкл' : 'Выкл' }}
                        </span>
                    </li>
                    <li v-if="summary.overduePayments > 0" class="flex items-center justify-between">
                        <span class="text-slate-500">Просрочка</span>
                        <span class="text-red-400">{{ summary.overduePayments }}</span>
                    </li>
                    <li v-if="client.crm_external_id" class="flex items-center justify-between">
                        <span class="text-slate-500">CRM</span>
                        <span class="font-mono text-violet-400">#{{ client.crm_external_id }}</span>
                    </li>
                </ul>

                <div
                    v-if="telegramStatus === 'sibling'"
                    class="mt-3 rounded-lg border border-amber-500/20 bg-amber-500/5 p-2.5 text-[11px] leading-relaxed text-amber-200/90"
                >
                    Telegram привязан к карточке «{{ summary.telegram.sibling_name }}» (#{{ summary.telegram.sibling_id }}).
                    <button
                        type="button"
                        class="mt-2 block w-full rounded-md bg-amber-600/80 px-2 py-1.5 text-center text-[11px] font-medium text-white hover:bg-amber-500"
                        :disabled="profile.loading"
                        @click="onClaimTelegram"
                    >
                        Привязать к этой карточке
                    </button>
                </div>

                <div v-if="client.link_token && config.botUsername" class="mt-4 border-t border-slate-800 pt-3">
                    <div class="text-slate-600">Ссылка бота</div>
                    <a
                        :href="`https://t.me/${config.botUsername}?start=link_${client.link_token}`"
                        target="_blank"
                        rel="noopener"
                        class="mt-1 block break-all text-sky-400 hover:underline"
                    >
                        t.me/{{ config.botUsername }}
                    </a>
                    <p class="mt-1 text-[10px] text-slate-600">Откройте в Telegram — привяжется именно эта карточка.</p>
                </div>
            </div>
        </div>
    </aside>
</template>
