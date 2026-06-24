<script setup>
import { computed, provide, ref, watch } from 'vue';
import { useClientProfile } from './useClientProfile';
import { groupPayments, periodWeeksFromContract } from './paymentsGrouping';
import ClientSidebar from './components/ClientSidebar.vue';
import ProfileTab from './tabs/ProfileTab.vue';
import PhonesTab from './tabs/PhonesTab.vue';
import VehiclesTab from './tabs/VehiclesTab.vue';
import ContractsTab from './tabs/ContractsTab.vue';
import PaymentsTab from './tabs/PaymentsTab.vue';
import InsurancesTab from './tabs/InsurancesTab.vue';
import MaintenancesTab from './tabs/MaintenancesTab.vue';

const props = defineProps({
    initial: { type: Object, required: true },
});

const profile = useClientProfile(props.initial);
provide('profile', profile);

const tabs = [
    { id: 'profile', label: 'Профиль' },
    { id: 'phones', label: 'Телефоны', count: () => profile.client.phones.length },
    { id: 'vehicles', label: 'Авто', count: () => profile.client.vehicles.length },
    { id: 'contracts', label: 'Договор', count: () => profile.client.contracts.length },
    { id: 'payments', label: 'Платежи', count: () => {
        const { groups } = groupPayments(
            profile.client.payments ?? [],
            periodWeeksFromContract(profile.client.contracts),
        );
        const open = groups.filter((g) => g.status !== 'paid').length;
        return open || null;
    } },
    { id: 'insurances', label: 'Страховка', count: () => profile.client.insurances.length },
    { id: 'maintenances', label: 'ТО', count: () => profile.client.maintenances.length },
];

const tabComponents = {
    profile: ProfileTab,
    phones: PhonesTab,
    vehicles: VehiclesTab,
    contracts: ContractsTab,
    payments: PaymentsTab,
    insurances: InsurancesTab,
    maintenances: MaintenancesTab,
};

const allowed = tabs.map((t) => t.id);
const initialTab = new URLSearchParams(window.location.search).get('tab');
const activeTab = ref(allowed.includes(initialTab) ? initialTab : 'profile');

function setTab(id) {
    activeTab.value = id;
    const url = new URL(window.location.href);
    url.searchParams.set('tab', id);
    window.history.replaceState({}, '', url);
}

watch(activeTab, (id) => {
    const url = new URL(window.location.href);
    if (url.searchParams.get('tab') !== id) {
        url.searchParams.set('tab', id);
        window.history.replaceState({}, '', url);
    }
});

const activeLabel = computed(() => tabs.find((t) => t.id === activeTab.value)?.label ?? 'Профиль');
const ActiveComponent = computed(() => tabComponents[activeTab.value]);
</script>

<template>
    <div>
        <div class="mb-5">
            <a :href="profile.urls.index" class="inline-flex items-center gap-1 text-xs text-slate-500 transition hover:text-sky-400">
                <span>←</span> Все клиенты
            </a>
        </div>

        <Transition
            enter-active-class="transition duration-200"
            enter-from-class="opacity-0 -translate-y-1"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="profile.message"
                class="mb-4 rounded-lg border px-4 py-3 text-sm"
                :class="Object.keys(profile.errors ?? {}).length
                    ? 'border-red-500/30 bg-red-500/10 text-red-300'
                    : 'border-emerald-500/30 bg-emerald-500/10 text-emerald-300'"
            >
                {{ profile.message }}
            </div>
        </Transition>

        <div class="flex gap-6">
            <div class="flex min-w-0 flex-1 gap-5">
                <nav class="hidden w-40 shrink-0 md:block" aria-label="Разделы клиента">
                    <ul class="space-y-0.5">
                        <li v-for="tab in tabs" :key="tab.id">
                            <button
                                type="button"
                                class="flex w-full items-center justify-between rounded-xl px-3 py-2.5 text-left text-sm transition"
                                :class="activeTab === tab.id
                                    ? 'bg-sky-500/10 font-medium text-sky-300 ring-1 ring-sky-500/20'
                                    : 'text-slate-400 hover:bg-slate-900 hover:text-slate-200'"
                                @click="setTab(tab.id)"
                            >
                                <span>{{ tab.label }}</span>
                                <span
                                    v-if="tab.count?.()"
                                    class="rounded-full bg-slate-800 px-1.5 py-0.5 text-[10px] text-slate-400"
                                >
                                    {{ tab.count() }}
                                </span>
                            </button>
                        </li>
                    </ul>
                </nav>

                <div class="min-w-0 flex-1">
                    <div class="mb-4 flex gap-1 overflow-x-auto pb-1 md:hidden">
                        <button
                            v-for="tab in tabs"
                            :key="tab.id"
                            type="button"
                            class="shrink-0 rounded-full px-3 py-1.5 text-xs transition"
                            :class="activeTab === tab.id ? 'bg-sky-600 text-white' : 'bg-slate-800 text-slate-400'"
                            @click="setTab(tab.id)"
                        >
                            {{ tab.label }}
                        </button>
                    </div>

                    <div class="rounded-2xl border border-slate-800/80 bg-slate-900/40">
                        <div class="border-b border-slate-800/80 px-5 py-4 md:px-6">
                            <h1 class="text-lg font-semibold tracking-tight">{{ activeLabel }}</h1>
                            <p class="mt-0.5 text-xs text-slate-500">{{ profile.client.full_name }}</p>
                        </div>
                        <div class="px-5 py-5 md:px-6 md:py-6">
                            <Transition
                                mode="out-in"
                                enter-active-class="transition duration-200 ease-out"
                                enter-from-class="opacity-0 translate-y-1"
                                enter-to-class="opacity-100 translate-y-0"
                                leave-active-class="transition duration-150 ease-in"
                                leave-from-class="opacity-100"
                                leave-to-class="opacity-0"
                            >
                                <div :key="activeTab">
                                    <component :is="ActiveComponent" />
                                </div>
                            </Transition>
                        </div>
                    </div>
                </div>
            </div>

            <ClientSidebar />
        </div>

        <div
            v-if="profile.loading"
            class="pointer-events-none fixed inset-0 z-50 flex items-start justify-center bg-slate-950/20 pt-24"
        >
            <div class="h-8 w-8 animate-spin rounded-full border-2 border-sky-500 border-t-transparent" />
        </div>
    </div>
</template>
