<script setup>
import { computed, onBeforeUnmount, ref } from 'vue';

const props = defineProps({
    media: { type: Object, required: true },
    removable: { type: Boolean, default: false },
});

const emit = defineEmits(['remove']);

const objectUrls = ref(new Map());

const ext = computed(() => (props.media.name || '').split('.').pop()?.toLowerCase() || '');

const type = computed(() => props.media.type || props.media.mime || '');

const isImage = computed(() =>
    type.value.startsWith('image/') || ['jpg', 'jpeg', 'png', 'webp', 'gif'].includes(ext.value),
);

const previewUrl = computed(() => {
    if (props.media.url) return props.media.url;
    if (props.media.file) {
        if (!objectUrls.value.has(props.media.file)) {
            objectUrls.value.set(props.media.file, URL.createObjectURL(props.media.file));
        }
        return objectUrls.value.get(props.media.file);
    }
    return null;
});

const icon = computed(() => {
    if (ext.value === 'pdf') return '📄';
    if (ext.value === 'xls' || ext.value === 'xlsx') return '📊';
    if (isImage.value) return '🖼️';
    return '🎬';
});

const sizeLabel = computed(() => {
    const bytes = props.media.size;
    if (!bytes && bytes !== 0) return '';
    const units = ['B', 'KB', 'MB', 'GB'];
    let i = 0;
    let n = bytes;
    while (n >= 1024 && i < units.length - 1) {
        n /= 1024;
        i += 1;
    }
    return `${n.toFixed(i === 0 ? 0 : 1)} ${units[i]}`;
});

onBeforeUnmount(() => {
    objectUrls.value.forEach((url) => URL.revokeObjectURL(url));
});
</script>

<template>
    <div class="min-w-0">
        <div class="group relative aspect-square overflow-hidden rounded-xl bg-slate-100 ring-1 ring-inset ring-slate-200">
            <img
                v-if="isImage && previewUrl"
                :src="previewUrl"
                :alt="media.name"
                class="h-full w-full object-cover"
                loading="lazy"
            />
            <div v-else class="flex h-full w-full flex-col items-center justify-center gap-1 text-slate-400">
                <span class="text-2xl leading-none">{{ icon }}</span>
                <span class="max-w-full truncate px-1 text-[10px] font-semibold uppercase tracking-wider">
                    {{ ext || 'file' }}
                </span>
            </div>
            <button
                v-if="removable"
                type="button"
                class="absolute right-1.5 top-1.5 rounded-md bg-white/95 p-1 text-slate-500 shadow-sm ring-1 ring-slate-200 transition hover:text-rose-600"
                title="Remove file"
                @click="emit('remove')"
            >
                <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <p class="mt-1.5 truncate text-xs font-medium text-slate-600" :title="media.name">{{ media.name }}</p>
        <p v-if="sizeLabel" class="text-[10px] text-slate-400">{{ sizeLabel }}</p>
    </div>
</template>
