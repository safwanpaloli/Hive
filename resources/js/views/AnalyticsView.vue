<script setup>
import { computed, onMounted, ref } from 'vue';
import { usePostsStore } from '../stores/posts';
import { useAccountsStore } from '../stores/accounts';
import { formatDate } from '../utils/format';

const posts = usePostsStore();
const accounts = useAccountsStore();

const from = ref('');
const to = ref('');
const platform = ref('');

const statsArray = computed(() =>
    Object.entries(posts.history.stats || {})
        .sort(([a], [b]) => (a < b ? 1 : -1))
        .map(([date, stat]) => ({ date, ...stat })),
);

const totalPosted = computed(() => posts.history.total || 0);

const maxCount = computed(() =>
    Math.max(1, ...statsArray.value.map((s) => s.count)),
);

const mostActivePlatform = computed(() => {
    const freq = {};
    statsArray.value.forEach((s) =>
        (s.platforms || []).forEach((p) => {
            freq[p] = (freq[p] || 0) + 1;
        }),
    );
    const entries = Object.entries(freq).sort((a, b) => b[1] - a[1]);
    return entries[0] ? { name: entries[0][0], count: entries[0][1] } : null;
});

onMounted(() => {
    Promise.all([accounts.fetchAccounts().catch(() => {}), load()]);
});

async function load() {
    await posts.fetchHistory({
        from: from.value || undefined,
        to: to.value || undefined,
    });
}
</script>

<template>
    <div class="space-y-6">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Total posted</p>
                <p class="mt-2 text-3xl font-bold text-slate-900">{{ totalPosted }}</p>
            </div>
            <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Active days</p>
                <p class="mt-2 text-3xl font-bold text-slate-900">{{ statsArray.length }}</p>
            </div>
            <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Most active platform</p>
                <p class="mt-2 truncate text-2xl font-bold text-slate-900">
                    {{ mostActivePlatform ? mostActivePlatform.name : '—' }}
                </p>
                <p v-if="mostActivePlatform" class="mt-1 text-xs text-slate-400">
                    {{ mostActivePlatform.count }} post(s) across logged days
                </p>
            </div>
        </div>

        <div class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
            <div class="flex flex-wrap items-center gap-3 border-b border-slate-100 px-6 py-4">
                <h3 class="mr-auto text-sm font-semibold text-slate-900">Posting history</h3>
                <input
                    v-model="from"
                    type="date"
                    class="rounded-xl border-0 bg-slate-50 px-3 py-2 text-sm shadow-sm ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-brand-500"
                    @change="load"
                />
                <input
                    v-model="to"
                    type="date"
                    class="rounded-xl border-0 bg-slate-50 px-3 py-2 text-sm shadow-sm ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-brand-500"
                    @change="load"
                />
                <select
                    v-model="platform"
                    class="rounded-xl border-0 bg-slate-50 px-3 py-2 text-sm shadow-sm ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-brand-500"
                    @change="load"
                >
                    <option value="">All platforms</option>
                    <option v-for="p in accounts.platformNames" :key="p" :value="p">{{ p }}</option>
                </select>
            </div>

            <div v-if="posts.loading" class="space-y-2 p-6">
                <div v-for="i in 5" :key="i" class="animate-pulse">
                    <div class="h-12 rounded-lg bg-slate-100"></div>
                </div>
            </div>

            <div v-else-if="statsArray.length === 0" class="p-12 text-center">
                <p class="text-3xl">📈</p>
                <h3 class="mt-3 text-lg font-semibold text-slate-900">No posting history yet</h3>
                <p class="mt-1 text-sm text-slate-500">
                    Once you mark posts as posted, they'll show up here as your consistency log.
                </p>
            </div>

            <ul v-else class="divide-y divide-slate-100">
                <li
                    v-for="item in statsArray"
                    :key="item.date"
                    class="flex items-center gap-4 px-6 py-4"
                >
                    <div class="w-36 shrink-0">
                        <p class="text-sm font-semibold text-slate-900">{{ formatDate(item.date) }}</p>
                        <p class="text-xs text-slate-400">
                            {{ item.count }} post{{ item.count === 1 ? '' : 's' }}
                        </p>
                    </div>
                    <div class="h-2.5 min-w-0 flex-1 overflow-hidden rounded-full bg-slate-100">
                        <div
                            class="h-full rounded-full bg-brand-500"
                            :style="{ width: `${Math.max(6, (item.count / maxCount) * 100)}%` }"
                        ></div>
                    </div>
                    <div class="flex shrink-0 flex-wrap justify-end gap-1.5">
                        <span
                            v-for="p in item.platforms"
                            :key="p"
                            class="rounded-md bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600"
                        >
                            {{ p }}
                        </span>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</template>
