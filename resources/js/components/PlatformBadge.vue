<script setup>
import { humanizePlatform } from '../utils/format';

const props = defineProps({
    platform: { type: String, required: true },
    size: { type: String, default: 'sm' },
});

const sizes = {
    sm: 'text-xs px-2 py-0.5',
    md: 'text-sm px-2.5 py-1',
};
</script>

<template>
    <span
        class="inline-flex items-center gap-1.5 rounded-md bg-white font-medium text-slate-700 ring-1 ring-inset ring-slate-200"
        :class="sizes[size]"
    >
        <span class="text-[0.85em] leading-none">{{ humanizePlatform(platform).icon }}</span>
        {{ platform }}
    </span>
</template>
