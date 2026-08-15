<script setup>
import { computed } from 'vue';

const props = defineProps({
    status: { type: String, default: 'draft' },
});

const styles = {
    draft: 'bg-slate-100 text-slate-700 ring-slate-200',
    scheduled: 'bg-brand-50 text-brand-700 ring-brand-200',
    posted: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
    skipped: 'bg-amber-50 text-amber-700 ring-amber-200',
};

const dots = {
    draft: 'bg-slate-400',
    scheduled: 'bg-brand-500',
    posted: 'bg-emerald-500',
    skipped: 'bg-amber-500',
};

const label = computed(() => props.status.charAt(0).toUpperCase() + props.status.slice(1));
</script>

<template>
    <span
        class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium capitalize ring-1 ring-inset"
        :class="styles[status] || styles.draft"
    >
        <span class="size-1.5 rounded-full" :class="dots[status] || dots.draft"></span>
        {{ label }}
    </span>
</template>
