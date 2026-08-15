<script setup>
import { useToast } from '../composables/useToast';

const { toasts } = useToast();

const icons = {
    success: '✓',
    error: '✕',
    info: 'ℹ',
};

const styles = {
    success: 'bg-emerald-600',
    error: 'bg-rose-600',
    info: 'bg-slate-800',
};
</script>

<template>
    <Teleport to="body">
        <div class="pointer-events-none fixed bottom-6 right-6 z-[100] flex w-80 flex-col gap-2">
            <TransitionGroup name="toast">
                <div
                    v-for="toast in toasts"
                    :key="toast.id"
                    class="pointer-events-auto flex items-start gap-3 rounded-xl px-4 py-3 text-sm font-medium text-white shadow-lg shadow-black/10"
                    :class="styles[toast.type]"
                >
                    <span class="mt-0.5 flex size-4 shrink-0 items-center justify-center rounded-full bg-white/20 text-[10px]">
                        {{ icons[toast.type] }}
                    </span>
                    <span class="leading-snug">{{ toast.message }}</span>
                </div>
            </TransitionGroup>
        </div>
    </Teleport>
</template>

<style scoped>
.toast-enter-active,
.toast-leave-active {
    transition: all 0.25s ease;
}

.toast-enter-from {
    opacity: 0;
    transform: translateY(12px);
}

.toast-leave-to {
    opacity: 0;
    transform: translateX(24px);
}
</style>
