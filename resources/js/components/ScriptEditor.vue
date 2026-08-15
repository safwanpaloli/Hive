<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { usePostsStore } from '../stores/posts';
import { useAccountsStore } from '../stores/accounts';
import DateTimePicker from './DateTimePicker.vue';
import MediaAttachment from './MediaAttachment.vue';
import { useToast } from '../composables/useToast';

const props = defineProps({
    post: { type: Object, default: null },
});

const emit = defineEmits(['close', 'saved']);

const posts = usePostsStore();
const accounts = useAccountsStore();
const toast = useToast();

const saving = ref(false);
const errors = ref({});

const ALLOWED_MEDIA_EXTENSIONS = ['pdf', 'xls', 'xlsx', 'jpg', 'jpeg', 'mp4', 'mov', 'webm', 'avi', 'mkv'];
const MAX_MEDIA_BYTES = 26 * 1024 * 1024;
const MAX_MEDIA_COUNT = 20;

const fileInput = ref(null);
const mediaFiles = ref([]);
const fileErrors = ref([]);

const form = reactive({
    title: '',
    script_body: '',
    scheduled_at: null,
    status: 'draft',
    media_links: '',
    platform_ids: [],
});

const isEditing = computed(() => Boolean(props.post?.id));

const selectedAccountNames = computed(() =>
    form.platform_ids
        .map((id) => accounts.byId(id))
        .filter(Boolean)
        .map((a) => a.platform_name),
);

const characterCount = computed(() => form.script_body.length);

watch(
    () => props.post,
    (post) => {
        if (post) {
            form.title = post.title || '';
            form.script_body = post.script_body || '';
            form.scheduled_at = post.scheduled_at ? new Date(post.scheduled_at) : null;
            form.status = post.status || 'draft';
            form.media_links = (post.media_links || []).join('\n');
            form.platform_ids = (post.platforms || []).map((p) => p.id);
            mediaFiles.value = (post.media_files || []).map((m, i) => ({
                id: `existing-${post.id}-${i}`,
                name: m.name || 'file',
                size: m.size || 0,
                type: m.mime || '',
                url: m.url,
                existing: true,
            }));
        } else {
            form.title = '';
            form.script_body = '';
            form.scheduled_at = null;
            form.status = 'draft';
            form.media_links = '';
            form.platform_ids = [];
            mediaFiles.value = [];
        }
        errors.value = {};
        fileErrors.value = [];
    },
    { immediate: true, deep: true },
);

function togglePlatform(id) {
    const idx = form.platform_ids.indexOf(id);
    if (idx === -1) form.platform_ids.push(id);
    else form.platform_ids.splice(idx, 1);
}

function parseMediaLinks() {
    return form.media_links
        .split('\n')
        .map((l) => l.trim())
        .filter(Boolean);
}

function onFilesSelected(e) {
    const files = Array.from(e.target.files || []);
    e.target.value = '';
    fileErrors.value = [];

    for (const file of files) {
        const ext = file.name.split('.').pop()?.toLowerCase();

        if (!ext || !ALLOWED_MEDIA_EXTENSIONS.includes(ext)) {
            fileErrors.value.push(`"${file.name}" — only PDF, Excel, JPG or video files are allowed.`);
            continue;
        }

        if (file.size >= MAX_MEDIA_BYTES) {
            fileErrors.value.push(`"${file.name}" — file must be smaller than 26MB.`);
            continue;
        }

        if (mediaFiles.value.filter((m) => !m.existing).length >= MAX_MEDIA_COUNT) {
            fileErrors.value.push(`You can attach up to ${MAX_MEDIA_COUNT} files per script.`);
            break;
        }

        mediaFiles.value.push({
            id: `new-${Date.now()}-${Math.random().toString(36).slice(2, 7)}`,
            name: file.name,
            size: file.size,
            type: file.type || '',
            file,
            existing: false,
        });
    }
}

function removeMedia(id) {
    mediaFiles.value = mediaFiles.value.filter((m) => m.id !== id);
}

function buildPayload() {
    const base = {
        title: form.title,
        script_body: form.script_body,
        scheduled_at: form.scheduled_at ? form.scheduled_at.toISOString() : null,
        status: form.status,
        platform_ids: form.platform_ids,
    };

    const existing = mediaFiles.value.filter((m) => m.existing);
    const fresh = mediaFiles.value.filter((m) => !m.existing);
    const keptUrls = new Set(existing.map((m) => m.url).filter(Boolean));
    const removedAny = (props.post?.media_files || []).some((m) => !keptUrls.has(m.url));

    if (fresh.length === 0 && !removedAny) {
        return { ...base, media_links: parseMediaLinks() };
    }

    const fd = new FormData();
    fd.append('title', base.title);
    fd.append('script_body', base.script_body || '');
    if (base.scheduled_at) fd.append('scheduled_at', base.scheduled_at);
    fd.append('status', base.status);
    parseMediaLinks().forEach((link) => fd.append('media_links[]', link));
    base.platform_ids.forEach((id) => fd.append('platform_ids[]', String(id)));
    fd.append(
        'existing_media_files',
        JSON.stringify(existing.map((m) => ({ url: m.url, name: m.name, size: m.size, mime: m.type }))),
    );
    fresh.forEach((m) => fd.append('media_files[]', m.file, m.name));

    return fd;
}

async function save() {
    saving.value = true;
    errors.value = {};

    const payload = buildPayload();

    try {
        if (isEditing.value) {
            await posts.updatePost(props.post.id, payload);
            toast.success('Script updated.');
        } else {
            await posts.createPost(payload);
            toast.success('Script created.');
        }
        emit('saved');
        emit('close');
    } catch (e) {
        errors.value = e.response?.data?.errors || {};
        toast.error('Please review the highlighted fields.');
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <form class="space-y-5" @submit.prevent="save">
        <div>
            <label for="script-title" class="mb-1.5 block text-sm font-medium text-slate-700">Title</label>
            <input
                id="script-title"
                v-model="form.title"
                type="text"
                required
                class="w-full rounded-xl border-0 bg-white px-3.5 py-2.5 text-sm font-medium shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-brand-500"
                placeholder="e.g. Product launch teaser"
            />
            <p v-if="errors.title" class="mt-1 text-xs text-rose-600">{{ errors.title[0] }}</p>
        </div>

        <div>
            <div class="mb-1.5 flex items-center justify-between">
                <label for="script-body" class="text-sm font-medium text-slate-700">Script / Caption</label>
                <span
                    class="text-xs"
                    :class="characterCount > 280 ? 'text-rose-600 font-medium' : 'text-slate-400'"
                >
                    {{ characterCount }} chars
                </span>
            </div>
            <textarea
                id="script-body"
                v-model="form.script_body"
                rows="9"
                class="w-full resize-y rounded-xl border-0 bg-white px-3.5 py-3 font-mono text-sm leading-relaxed shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-brand-500"
                placeholder="Write your caption, hashtags and CTA here…"
            ></textarea>
            <p v-if="errors.script_body" class="mt-1 text-xs text-rose-600">{{ errors.script_body[0] }}</p>
        </div>

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
            <div>
                <label for="script-date" class="mb-1.5 block text-sm font-medium text-slate-700">Schedule for</label>
                <DateTimePicker
                    id="script-date"
                    v-model="form.scheduled_at"
                    :invalid="Boolean(errors.scheduled_at)"
                />
                <p v-if="errors.scheduled_at" class="mt-1 text-xs text-rose-600">{{ errors.scheduled_at[0] }}</p>
            </div>

            <div>
                <label for="script-status" class="mb-1.5 block text-sm font-medium text-slate-700">Status</label>
                <select
                    id="script-status"
                    v-model="form.status"
                    class="w-full rounded-xl border-0 bg-white px-3.5 py-2.5 text-sm shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-500"
                >
                    <option value="draft">Draft</option>
                    <option value="scheduled">Scheduled</option>
                    <option value="posted">Posted</option>
                    <option value="skipped">Skipped</option>
                </select>
            </div>
        </div>

        <div>
            <label for="media-links" class="mb-1.5 block text-sm font-medium text-slate-700">
                Media link ideas <span class="font-normal text-slate-400">(one per line)</span>
            </label>
            <textarea
                id="media-links"
                v-model="form.media_links"
                rows="3"
                class="w-full resize-y rounded-xl border-0 bg-white px-3.5 py-2.5 text-sm shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-brand-500"
                placeholder="https://images.unsplash.com/…&#10;https://drive.google.com/file/…"
            ></textarea>
            <p v-if="errors['media_links.0']" class="mt-1 text-xs text-rose-600">{{ errors['media_links.0'][0] }}</p>
        </div>

        <div>
            <span class="mb-1.5 block text-sm font-medium text-slate-700">
                Media uploads
                <span class="font-normal text-slate-400">(PDF, Excel, JPG, video · max 26MB each)</span>
            </span>
            <div class="space-y-2">
                <p
                    v-if="mediaFiles.length === 0 && fileErrors.length === 0"
                    class="rounded-xl border-2 border-dashed border-slate-200 bg-white px-4 py-6 text-center text-sm text-slate-400"
                >
                    No files attached yet.
                </p>

                <div v-if="mediaFiles.length" class="grid grid-cols-3 gap-2.5 sm:grid-cols-4">
                    <MediaAttachment
                        v-for="media in mediaFiles"
                        :key="media.id"
                        :media="media"
                        removable
                        @remove="removeMedia(media.id)"
                    />
                </div>

                <p v-if="fileErrors.length" class="text-xs text-rose-600">
                    <span v-for="(err, i) in fileErrors" :key="i">
                        {{ err }}<br v-if="i < fileErrors.length - 1" />
                    </span>
                </p>
            </div>

            <button
                type="button"
                class="mt-2 inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-sm font-medium text-slate-600 shadow-sm transition hover:border-brand-300 hover:text-brand-600"
                @click="fileInput?.click()"
            >
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>
                Add file
            </button>
            <input
                ref="fileInput"
                type="file"
                multiple
                accept=".pdf,.xls,.xlsx,.jpg,.jpeg,.mp4,.mov,.webm,.avi,.mkv"
                class="hidden"
                @change="onFilesSelected"
            />
        </div>

        <div>
            <span class="mb-1.5 block text-sm font-medium text-slate-700">Tag platforms</span>
            <div v-if="accounts.loading" class="text-sm text-slate-400">Loading accounts…</div>
            <div v-else-if="accounts.accounts.length === 0" class="rounded-xl bg-amber-50 px-3.5 py-2.5 text-sm text-amber-700 ring-1 ring-inset ring-amber-200">
                No accounts yet — add some in the <RouterLink :to="{ name: 'accounts' }" class="font-semibold underline">Account Vault</RouterLink>.
            </div>
            <div v-else class="flex flex-wrap gap-2">
                <button
                    v-for="account in accounts.accounts"
                    :key="account.id"
                    type="button"
                    class="rounded-xl px-3 py-2 text-sm font-medium ring-1 ring-inset transition"
                    :class="
                        form.platform_ids.includes(account.id)
                            ? 'bg-brand-600 text-white ring-brand-600'
                            : 'bg-white text-slate-600 ring-slate-200 hover:bg-slate-50'
                    "
                    @click="togglePlatform(account.id)"
                >
                    {{ account.platform_name }}
                    <span class="ml-1 text-xs opacity-70">{{ account.handle }}</span>
                </button>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-5">
            <button
                type="button"
                class="rounded-xl px-4 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-100"
                @click="emit('close')"
            >
                Cancel
            </button>
            <button
                type="submit"
                :disabled="saving"
                class="rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700 disabled:opacity-60"
            >
                {{ saving ? 'Saving…' : isEditing ? 'Save changes' : 'Create script' }}
            </button>
        </div>
    </form>
</template>
