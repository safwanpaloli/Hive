<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { usePostsStore } from '../stores/posts';
import { useAccountsStore } from '../stores/accounts';
import { toLocalDatetime } from '../utils/format';
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

const form = reactive({
    title: '',
    script_body: '',
    scheduled_at: '',
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
            form.scheduled_at = toLocalDatetime(post.scheduled_at);
            form.status = post.status || 'draft';
            form.media_links = (post.media_links || []).join('\n');
            form.platform_ids = (post.platforms || []).map((p) => p.id);
        } else {
            form.title = '';
            form.script_body = '';
            form.scheduled_at = '';
            form.status = 'draft';
            form.media_links = '';
            form.platform_ids = [];
        }
        errors.value = {};
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

async function save() {
    saving.value = true;
    errors.value = {};

    const payload = {
        title: form.title,
        script_body: form.script_body,
        scheduled_at: form.scheduled_at ? new Date(form.scheduled_at).toISOString() : null,
        status: form.status,
        media_links: parseMediaLinks(),
        platform_ids: form.platform_ids,
    };

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
                <input
                    id="script-date"
                    v-model="form.scheduled_at"
                    type="datetime-local"
                    class="w-full rounded-xl border-0 bg-white px-3.5 py-2.5 text-sm shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-500"
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
