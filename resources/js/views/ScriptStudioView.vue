<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { usePostsStore } from '../stores/posts';
import { useAccountsStore } from '../stores/accounts';
import ScriptEditor from '../components/ScriptEditor.vue';
import ScriptDetailModal from '../components/ScriptDetailModal.vue';
import StatusBadge from '../components/StatusBadge.vue';
import PlatformBadge from '../components/PlatformBadge.vue';
import { formatDateTime } from '../utils/format';
import { useToast } from '../composables/useToast';

const route = useRoute();
const router = useRouter();
const posts = usePostsStore();
const accounts = useAccountsStore();
const toast = useToast();

const editorOpen = ref(false);
const editingPost = ref(null);
const detailOpen = ref(false);
const detailPost = ref(null);
const deleteTarget = ref(null);

const filterStatus = ref(posts.filters.status || '');
const filterPlatform = ref(posts.filters.platform || '');
const filterFrom = ref(posts.filters.from || '');
const filterTo = ref(posts.filters.to || '');
const search = ref(posts.filters.q || '');

const statuses = ['draft', 'scheduled', 'posted', 'skipped'];

onMounted(async () => {
    await Promise.all([accounts.fetchAccounts().catch(() => {}), posts.fetchPosts()]);

    if (route.query.new) {
        openEditor(null);
    } else if (route.query.edit) {
        const post = posts.posts.find((p) => p.id === Number(route.query.edit));
        if (post) openEditor(post);
    }
});

function openEditor(post) {
    detailOpen.value = false;
    detailPost.value = null;
    editingPost.value = post;
    editorOpen.value = true;
    router.replace({ name: 'scripts' });
}

function copyScript(post) {
    openEditor({
        ...post,
        id: null,
        title: `${post.title} (Copy)`,
        status: 'draft',
    });
}

function openDetail(post) {
    detailPost.value = post;
    detailOpen.value = true;
}

function applyFilters() {
    posts.setFilter({
        status: filterStatus.value,
        platform: filterPlatform.value,
        from: filterFrom.value,
        to: filterTo.value,
        q: search.value,
    });
    posts.fetchPosts().catch(() => {});
}

function resetFilters() {
    filterStatus.value = '';
    filterPlatform.value = '';
    filterFrom.value = '';
    filterTo.value = '';
    search.value = '';
    posts.resetFilters();
    posts.fetchPosts().catch(() => {});
}

const hasActiveFilters = computed(
    () => filterStatus.value || filterPlatform.value || filterFrom.value || filterTo.value || search.value,
);

async function confirmDelete() {
    try {
        await posts.deletePost(deleteTarget.value.id);
        toast.success('Post deleted.');
    } catch {
        toast.error('Could not delete the post.');
    }
    deleteTarget.value = null;
}
</script>

<template>
    <div class="space-y-5">
        <div class="space-y-3">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <div class="relative flex-1">
                    <svg
                        class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400"
                        fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z" />
                    </svg>
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Search scripts…"
                        class="w-full rounded-xl border-0 bg-white py-2.5 pl-9 pr-3.5 text-sm shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-brand-500"
                        @keyup.enter="applyFilters"
                    />
                </div>
                <button
                    type="button"
                    class="w-full whitespace-nowrap rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700 sm:w-auto"
                    @click="openEditor(null)"
                >
                    + New script
                </button>
            </div>
            <div class="grid grid-cols-2 gap-3 sm:flex sm:flex-wrap sm:items-center">
                <select
                    v-model="filterStatus"
                    class="rounded-xl border-0 bg-white px-3 py-2.5 text-sm shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-500"
                    @change="applyFilters"
                >
                    <option value="">All statuses</option>
                    <option v-for="s in statuses" :key="s" :value="s">{{ s[0].toUpperCase() + s.slice(1) }}</option>
                </select>
                <select
                    v-model="filterPlatform"
                    class="rounded-xl border-0 bg-white px-3 py-2.5 text-sm shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-500"
                    @change="applyFilters"
                >
                    <option value="">All platforms</option>
                    <option v-for="p in accounts.platformNames" :key="p" :value="p">{{ p }}</option>
                </select>
                <input
                    v-model="filterFrom"
                    type="date"
                    class="rounded-xl border-0 bg-white px-3 py-2.5 text-sm shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-500"
                    @change="applyFilters"
                />
                <input
                    v-model="filterTo"
                    type="date"
                    class="rounded-xl border-0 bg-white px-3 py-2.5 text-sm shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-500"
                    @change="applyFilters"
                />
                <button
                    v-if="hasActiveFilters"
                    type="button"
                    class="rounded-xl bg-white px-3.5 py-2.5 text-sm font-medium text-slate-500 ring-1 ring-inset ring-slate-300 transition hover:text-slate-800"
                    @click="resetFilters"
                >
                    Clear
                </button>
            </div>
        </div>

        <div v-if="posts.loading" class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            <div v-for="i in 4" :key="i" class="animate-pulse rounded-2xl bg-white p-5 ring-1 ring-slate-200">
                <div class="h-4 w-1/2 rounded bg-slate-200"></div>
                <div class="mt-3 h-3 w-full rounded bg-slate-100"></div>
            </div>
        </div>

        <div v-else-if="posts.posts.length === 0" class="rounded-2xl border-2 border-dashed border-slate-200 bg-white p-12 text-center">
            <p class="text-3xl">📝</p>
            <h3 class="mt-3 text-lg font-semibold text-slate-900">No scripts found</h3>
            <p class="mt-1 text-sm text-slate-500">Try adjusting your filters or write a brand-new script.</p>
        </div>

        <div v-else class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            <article
                v-for="post in posts.posts"
                :key="post.id"
                class="flex cursor-pointer flex-col rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 transition hover:shadow-md hover:ring-brand-200"
                @click="openDetail(post)"
            >
                <div class="flex items-start justify-between gap-3">
                    <h3 class="font-semibold text-slate-900">{{ post.title }}</h3>
                    <StatusBadge :status="post.status" />
                </div>
                <p class="mt-2 line-clamp-3 text-sm text-slate-500">{{ post.script_body || 'No caption written yet.' }}</p>
                <div class="mt-3 flex flex-wrap items-center gap-2">
                    <PlatformBadge
                        v-for="platform in post.platforms"
                        :key="platform.id"
                        :platform="platform.platform_name"
                        size="sm"
                    />
                </div>
                <div class="mt-4 flex items-center justify-between border-t border-slate-100 pt-3">
                    <span class="text-xs text-slate-400">{{ formatDateTime(post.scheduled_at) }}</span>
                    <div class="flex items-center gap-1 sm:gap-2">
                        <span v-if="post.media_files?.length" class="hidden sm:inline-block text-xs text-slate-400" title="Has attached media">📎 {{ post.media_files.length }}</span>
                        <span v-if="post.media_links?.length" class="hidden sm:inline-block text-xs text-slate-400" title="Has media links">🔗 {{ post.media_links.length }}</span>
                        <button
                            type="button"
                            class="rounded-lg px-2.5 py-1 text-xs font-medium text-brand-600 transition hover:bg-brand-50"
                            @click.stop="copyScript(post)"
                        >
                            Copy
                        </button>
                        <button
                            type="button"
                            class="rounded-lg px-2.5 py-1 text-xs font-medium text-slate-600 transition hover:bg-slate-50"
                            @click.stop="openEditor(post)"
                        >
                            Edit
                        </button>
                        <button
                            type="button"
                            class="rounded-lg px-2.5 py-1 text-xs font-medium text-rose-600 transition hover:bg-rose-50"
                            @click.stop="deleteTarget = post"
                        >
                            Delete
                        </button>
                    </div>
                </div>
            </article>
        </div>

        <div v-if="posts.pagination.last_page > 1" class="flex items-center justify-between">
            <button
                type="button"
                :disabled="posts.pagination.current_page <= 1"
                class="rounded-xl bg-white px-4 py-2 text-sm font-medium text-slate-600 ring-1 ring-inset ring-slate-300 transition hover:bg-slate-50 disabled:opacity-40"
                @click="posts.fetchPosts({ page: posts.pagination.current_page - 1 })"
            >
                ← Previous
            </button>
            <span class="text-sm text-slate-500">
                Page {{ posts.pagination.current_page }} of {{ posts.pagination.last_page }}
            </span>
            <button
                type="button"
                :disabled="posts.pagination.current_page >= posts.pagination.last_page"
                class="rounded-xl bg-white px-4 py-2 text-sm font-medium text-slate-600 ring-1 ring-inset ring-slate-300 transition hover:bg-slate-50 disabled:opacity-40"
                @click="posts.fetchPosts({ page: posts.pagination.current_page + 1 })"
            >
                Next →
            </button>
        </div>

        <Teleport to="body">
            <Transition name="modal">
                <div
                    v-if="editorOpen"
                    class="fixed inset-0 z-50 flex items-center justify-end bg-slate-900/40 p-0 backdrop-blur-sm"
                    @click.self="editorOpen = false"
                >
                    <div class="h-full w-full max-w-xl overflow-y-auto bg-slate-50 shadow-2xl">
                        <div class="sticky top-0 z-10 flex items-center justify-between border-b border-slate-200 bg-white px-6 py-4">
                            <h3 class="text-sm font-bold text-slate-900">
                                {{ editingPost ? 'Edit script' : 'New script' }}
                            </h3>
                            <button
                                type="button"
                                class="rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                                @click="editorOpen = false"
                            >
                                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                        <div class="p-6">
                            <ScriptEditor
                                :post="editingPost"
                                @close="editorOpen = false"
                                @saved="posts.fetchToday().catch(() => {})"
                            />
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <Teleport to="body">
            <Transition name="modal">
                <div
                    v-if="detailOpen"
                    class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4 backdrop-blur-sm"
                    @click.self="detailOpen = false"
                >
                    <div class="flex max-h-[90dvh] w-full max-w-lg flex-col overflow-hidden rounded-2xl bg-slate-50 shadow-2xl">
                        <ScriptDetailModal
                            :post="detailPost"
                            @close="detailOpen = false"
                            @edit="openEditor(detailPost)"
                        />
                    </div>
                </div>
            </Transition>
        </Teleport>

        <Teleport to="body">
            <Transition name="modal">
                <div
                    v-if="deleteTarget"
                    class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-900/40 p-4 backdrop-blur-sm"
                >
                    <div class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-2xl">
                        <h3 class="text-base font-bold text-slate-900">Delete this script?</h3>
                        <p class="mt-2 text-sm text-slate-500">
                            "{{ deleteTarget.title }}" will be permanently removed.
                        </p>
                        <div class="mt-6 flex justify-end gap-3">
                            <button
                                type="button"
                                class="rounded-xl px-4 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100"
                                @click="deleteTarget = null"
                            >
                                Cancel
                            </button>
                            <button
                                type="button"
                                class="rounded-xl bg-rose-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-rose-700"
                                @click="confirmDelete"
                            >
                                Delete
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>

<style scoped>
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.2s ease;
}
.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}
</style>
