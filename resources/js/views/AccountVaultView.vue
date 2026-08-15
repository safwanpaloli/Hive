<script setup>
import { computed, onMounted, ref } from 'vue';
import { useAccountsStore } from '../stores/accounts';
import { usePostsStore } from '../stores/posts';
import AccountFormModal from '../components/AccountFormModal.vue';
import { humanizePlatform } from '../utils/format';
import { useToast } from '../composables/useToast';

const accounts = useAccountsStore();
const posts = usePostsStore();
const toast = useToast();

const modalOpen = ref(false);
const editing = ref(null);
const deleteTarget = ref(null);

const search = ref('');
const platformFilter = ref('');

const filteredAccounts = computed(() => {
    const q = search.value.toLowerCase();
    return accounts.accounts.filter((a) => {
        const matchesQuery =
            !q ||
            a.platform_name.toLowerCase().includes(q) ||
            a.handle.toLowerCase().includes(q) ||
            (a.notes || '').toLowerCase().includes(q);
        const matchesPlatform = !platformFilter.value || a.platform_name === platformFilter.value;
        return matchesQuery && matchesPlatform;
    });
});

const totalAccounts = computed(() => accounts.accounts.length);
const uniquePlatforms = computed(() => accounts.platformNames);

onMounted(() => {
    accounts.fetchAccounts().catch(() => {});
});

function openNew() {
    editing.value = null;
    modalOpen.value = true;
}

function openEdit(account) {
    editing.value = account;
    modalOpen.value = true;
}

async function confirmDelete() {
    try {
        await accounts.deleteAccount(deleteTarget.value.id);
        toast.success('Account removed from vault.');
    } catch {
        toast.error('Could not delete the account.');
    }
    deleteTarget.value = null;
}
</script>

<template>
    <div class="space-y-5">
        <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
            <div class="relative flex-1 sm:min-w-56">
                <svg
                    class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400"
                    fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z" />
                </svg>
                <input
                    v-model="search"
                    type="text"
                    placeholder="Search vault…"
                    class="w-full rounded-xl border-0 bg-white py-2.5 pl-9 pr-3.5 text-sm shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-brand-500"
                />
            </div>
            <div class="flex gap-3">
                <select
                    v-model="platformFilter"
                    class="min-w-0 flex-1 rounded-xl border-0 bg-white px-3 py-2.5 text-sm shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-500 sm:flex-none"
                >
                    <option value="">All platforms</option>
                    <option v-for="p in uniquePlatforms" :key="p" :value="p">{{ p }}</option>
                </select>
                <button
                    type="button"
                    class="flex-1 whitespace-nowrap rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700 sm:flex-none"
                    @click="openNew"
                >
                    + Add account
                </button>
            </div>
        </div>

        <div v-if="accounts.loading" class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
            <div v-for="i in 6" :key="i" class="animate-pulse rounded-2xl bg-white p-5 ring-1 ring-slate-200">
                <div class="h-5 w-2/3 rounded bg-slate-200"></div>
                <div class="mt-3 h-3 w-1/2 rounded bg-slate-100"></div>
            </div>
        </div>

        <div v-else-if="accounts.accounts.length === 0" class="rounded-2xl border-2 border-dashed border-slate-200 bg-white p-12 text-center">
            <p class="text-3xl">🔐</p>
            <h3 class="mt-3 text-lg font-semibold text-slate-900">Your vault is empty</h3>
            <p class="mt-1 text-sm text-slate-500">Add your first social account to start planning posts.</p>
            <button
                type="button"
                class="mt-5 rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700"
                @click="openNew"
            >
                + Add account
            </button>
        </div>

        <div v-else class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
            <article
                v-for="account in filteredAccounts"
                :key="account.id"
                class="flex flex-col rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 transition hover:shadow-md"
            >
                <div class="flex items-start justify-between">
                    <div class="relative flex size-12 items-center justify-center overflow-hidden rounded-xl bg-brand-50 text-xl ring-1 ring-slate-200">
                        <img
                            v-if="account.avatar_url"
                            :src="account.avatar_url"
                            :alt="`${account.platform_name} avatar`"
                            class="size-full object-cover"
                        />
                        <span v-else>{{ humanizePlatform(account.platform_name).icon }}</span>
                    </div>
                    <span
                        v-if="account.account_type"
                        class="rounded-full bg-slate-100 px-2.5 py-0.5 text-[11px] font-medium text-slate-600"
                    >
                        {{ account.account_type }}
                    </span>
                </div>

                <h3 class="mt-3 font-semibold text-slate-900">{{ account.platform_name }}</h3>
                <p class="text-sm text-slate-500">{{ account.handle }}</p>

                <a
                    v-if="account.profile_url"
                    :href="account.profile_url"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="mt-2 inline-flex items-center gap-1 text-xs font-medium text-brand-600 hover:text-brand-700"
                >
                    Open profile
                    <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                    </svg>
                </a>

                <p v-if="account.notes" class="mt-3 line-clamp-2 rounded-lg bg-slate-50 px-3 py-2 text-xs text-slate-500">
                    {{ account.notes }}
                </p>

                <div class="mt-4 flex items-center justify-between border-t border-slate-100 pt-3">
                    <span class="text-xs text-slate-400">
                        {{ posts.today.posts.filter((p) => p.platforms?.some((pl) => pl.id === account.id)).length }}
                        in today's queue
                    </span>
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            class="rounded-lg px-2.5 py-1 text-xs font-medium text-slate-600 transition hover:bg-slate-100"
                            @click="openEdit(account)"
                        >
                            Edit
                        </button>
                        <button
                            type="button"
                            class="rounded-lg px-2.5 py-1 text-xs font-medium text-rose-600 transition hover:bg-rose-50"
                            @click="deleteTarget = account"
                        >
                            Remove
                        </button>
                    </div>
                </div>
            </article>
        </div>

        <p v-if="totalAccounts" class="text-center text-xs text-slate-400">
            {{ filteredAccounts.length }} of {{ totalAccounts }} account(s)
        </p>

        <Teleport to="body">
            <Transition name="modal">
                <div
                    v-if="modalOpen"
                    class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4 backdrop-blur-sm"
                    @click.self="modalOpen = false"
                >
                    <div class="max-h-[calc(100dvh-2rem)] w-full max-w-md overflow-y-auto rounded-2xl bg-white shadow-2xl">
                        <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                            <h3 class="text-sm font-bold text-slate-900">
                                {{ editing ? 'Edit account' : 'Add account' }}
                            </h3>
                            <button
                                type="button"
                                class="rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                                @click="modalOpen = false"
                            >
                                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                        <div class="p-6">
                            <AccountFormModal
                                :account="editing"
                                @close="modalOpen = false"
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
                    v-if="deleteTarget"
                    class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-900/40 p-4 backdrop-blur-sm"
                >
                    <div class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-2xl">
                        <h3 class="text-base font-bold text-slate-900">Remove this account?</h3>
                        <p class="mt-2 text-sm text-slate-500">
                            "{{ deleteTarget.platform_name }} · {{ deleteTarget.handle }}" will be removed from your vault.
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
                                Remove
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
