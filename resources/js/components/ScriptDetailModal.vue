<script setup>
import { computed } from 'vue';
import StatusBadge from './StatusBadge.vue';
import PlatformBadge from './PlatformBadge.vue';
import MediaAttachment from './MediaAttachment.vue';
import { formatDateTime } from '../utils/format';
import { useToast } from '../composables/useToast';

const props = defineProps({
    post: { type: Object, required: true },
});

const emit = defineEmits(['close', 'edit']);

const toast = useToast();

const scheduledLabel = computed(() => formatDateTime(props.post.scheduled_at));

async function copyUrl(url) {
    try {
        await navigator.clipboard.writeText(url);
        toast.success('URL copied.');
    } catch {
        toast.error('Could not copy URL.');
    }
}
</script>

<template>
    <div class="flex h-full flex-col">
        <div class="flex items-start justify-between gap-4 border-b border-slate-200 bg-white px-6 py-4">
            <div class="min-w-0">
                <h3 class="truncate text-base font-bold text-slate-900">{{ post.title }}</h3>
                <div class="mt-1.5 flex items-center gap-2">
                    <StatusBadge :status="post.status" />
                    <span class="text-xs text-slate-400">{{ scheduledLabel }}</span>
                </div>
            </div>
            <button
                type="button"
                class="shrink-0 rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                title="Close"
                @click="emit('close')"
            >
                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="flex-1 space-y-6 overflow-y-auto px-6 py-5">
            <div v-if="post.platforms?.length">
                <span class="mb-2 block text-xs font-semibold uppercase tracking-wider text-slate-400">Platforms</span>
                <div class="flex flex-wrap gap-2">
                    <PlatformBadge
                        v-for="platform in post.platforms"
                        :key="platform.id"
                        :platform="platform.platform_name"
                    />
                </div>
            </div>

            <div>
                <span class="mb-2 block text-xs font-semibold uppercase tracking-wider text-slate-400">Script</span>
                <p class="whitespace-pre-wrap rounded-xl bg-white p-4 text-sm leading-relaxed text-slate-700 ring-1 ring-inset ring-slate-200">
                    {{ post.script_body || 'No caption written yet.' }}
                </p>
            </div>

            <div v-if="post.media_links?.length">
                <span class="mb-2 block text-xs font-semibold uppercase tracking-wider text-slate-400">
                    Media links <span class="font-normal normal-case text-slate-300">({{ post.media_links.length }})</span>
                </span>
                <ul class="space-y-2">
                    <li
                        v-for="(link, i) in post.media_links"
                        :key="i"
                        class="flex items-center gap-2 rounded-xl bg-white px-3.5 py-2.5 ring-1 ring-inset ring-slate-200"
                    >
                        <svg class="size-4 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244" />
                        </svg>
                        <a
                            :href="link"
                            target="_blank"
                            rel="noopener"
                            class="min-w-0 truncate text-sm text-brand-600 hover:underline"
                        >
                            {{ link }}
                        </a>
                    </li>
                </ul>
            </div>

            <div v-if="post.media_files?.length">
                <span class="mb-2 block text-xs font-semibold uppercase tracking-wider text-slate-400">
                    Attachments <span class="font-normal normal-case text-slate-300">({{ post.media_files.length }})</span>
                </span>
                <div class="grid grid-cols-3 gap-3 sm:grid-cols-4">
                    <div v-for="media in post.media_files" :key="media.url" class="min-w-0">
                        <MediaAttachment :media="media" />
                        <div class="mt-1.5 flex items-center gap-1">
                            <a
                                :href="media.url"
                                target="_blank"
                                rel="noopener"
                                class="min-w-0 truncate text-[11px] text-brand-600 hover:underline"
                                :title="media.url"
                            >
                                {{ media.url }}
                            </a>
                            <button
                                type="button"
                                class="shrink-0 rounded p-0.5 text-slate-400 transition hover:text-slate-700"
                                title="Copy URL"
                                @click="copyUrl(media.url)"
                            >
                                <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 01-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 011.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 00-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 01-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 00-3.375-3.375h-1.5a1.125 1.125 0 01-1.125-1.125v-1.5a3.375 3.375 0 00-3.375-3.375H9.75" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-between border-t border-slate-200 bg-white px-6 py-4">
            <span class="text-xs text-slate-400">Created {{ formatDateTime(post.created_at) }}</span>
            <div class="flex items-center gap-2">
                <button
                    type="button"
                    class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700"
                    @click="emit('edit')"
                >
                    Edit script
                </button>
                <button
                    type="button"
                    class="rounded-xl bg-white px-4 py-2 text-sm font-medium text-slate-600 ring-1 ring-inset ring-slate-300 transition hover:bg-slate-50"
                    @click="emit('close')"
                >
                    Close
                </button>
            </div>
        </div>
    </div>
</template>
