<script setup>
import { onMounted, onUnmounted, watch } from 'vue';

const open = defineModel({ type: Boolean, default: false });

defineProps({
    title: { type: String, default: '' },
    size: { type: String, default: 'md' },
});

const emit = defineEmits(['close']);

const sizeClass = {
    sm: 'max-w-md',
    md: 'max-w-lg',
    lg: 'max-w-2xl',
};

function onKeydown(e) {
    if (e.key === 'Escape' && open.value) {
        close();
    }
}

function close() {
    open.value = false;
    emit('close');
}

watch(open, (v) => {
    document.body.style.overflow = v ? 'hidden' : '';
});

onMounted(() => window.addEventListener('keydown', onKeydown));
onUnmounted(() => {
    window.removeEventListener('keydown', onKeydown);
    document.body.style.overflow = '';
});
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="open"
                class="fixed inset-0 z-[100] flex items-end justify-center p-4 sm:items-center"
                role="dialog"
                aria-modal="true"
            >
                <button
                    type="button"
                    class="absolute inset-0 bg-slate-950/70 backdrop-blur-sm"
                    aria-label="Закрыть"
                    @click="close"
                />

                <Transition
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="opacity-0 translate-y-4 sm:translate-y-2 sm:scale-95"
                    enter-to-class="opacity-100 translate-y-0 sm:scale-100"
                    leave-active-class="transition duration-150 ease-in"
                    leave-from-class="opacity-100 translate-y-0 sm:scale-100"
                    leave-to-class="opacity-0 translate-y-4 sm:translate-y-2 sm:scale-95"
                >
                    <div
                        v-if="open"
                        class="relative z-10 w-full rounded-2xl border border-slate-700/80 bg-slate-900 shadow-2xl shadow-black/40"
                        :class="sizeClass[size] ?? sizeClass.md"
                    >
                        <div class="flex items-center justify-between border-b border-slate-800 px-5 py-4">
                            <h2 class="text-base font-semibold text-white">{{ title }}</h2>
                            <button
                                type="button"
                                class="rounded-lg p-1.5 text-slate-500 transition hover:bg-slate-800 hover:text-slate-300"
                                @click="close"
                            >
                                ✕
                            </button>
                        </div>
                        <div class="max-h-[min(70vh,560px)] overflow-y-auto px-5 py-4">
                            <slot />
                        </div>
                        <div v-if="$slots.footer" class="border-t border-slate-800 px-5 py-4">
                            <slot name="footer" />
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
