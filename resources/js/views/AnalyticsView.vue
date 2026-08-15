<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { usePostsStore } from '../stores/posts';
import { useAccountsStore } from '../stores/accounts';
import { formatDate, relativeTime, compactNumber, humanizePlatform } from '../utils/format';

const posts = usePostsStore();
const accounts = useAccountsStore();

const from = ref('');
const to = ref('');
const platform = ref('');
const refreshing = ref(false);

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

const overviewAccounts = computed(() => accounts.analytics?.accounts || []);
const feed = computed(() => accounts.analytics?.posts || []);
const totalFollowers = computed(() =>
    overviewAccounts.value.reduce((sum, a) => sum + (a.followers || 0), 0),
);
const isDemo = computed(() => Boolean(accounts.analytics?.demo));
const analyticsAccountCount = computed(() => accounts.accounts.length);

const FEED_PER_PAGE = 5;
const feedPage = ref(1);
const feedPages = computed(() => Math.max(1, Math.ceil(feed.value.length / FEED_PER_PAGE)));
const paginatedFeed = computed(() =>
    feed.value.slice((feedPage.value - 1) * FEED_PER_PAGE, feedPage.value * FEED_PER_PAGE),
);
const feedRange = computed(() => {
    const start = feed.value.length === 0 ? 0 : (feedPage.value - 1) * FEED_PER_PAGE + 1;
    const end = Math.min(feed.value.length, feedPage.value * FEED_PER_PAGE);
    return { start, end };
});

watch(feed, () => {
    if (feedPage.value > feedPages.value) feedPage.value = feedPages.value;
});

onMounted(() => {
    Promise.all([accounts.fetchAccounts().catch(() => {}), load()]);
});

async function load() {
    await Promise.all([
        posts.fetchHistory({
            from: from.value || undefined,
            to: to.value || undefined,
        }),
        accounts.fetchAnalytics(),
    ]);
}

async function refresh() {
    refreshing.value = true;
    try {
        await accounts.fetchAnalytics({ refresh: 1 });
    } finally {
        refreshing.value = false;
    }
}

function platformMeta(name) {
    return humanizePlatform(name);
}
</script>

<template>
    <div class="space-y-6">
        <div
            v-if="isDemo"
            class="flex items-start gap-3 rounded-2xl bg-amber-50 px-5 py-4 ring-1 ring-inset ring-amber-200"
        >
            <span class="text-lg leading-none">🧪</span>
            <div class="text-sm text-amber-800">
                <p class="font-semibold">Showing sample data</p>
                <p class="mt-0.5">
                    Connect real API credentials in the Account Vault (YouTube API key, Facebook /
                    Instagram access token) to see live followers, views, likes and comments.
                </p>
            </div>
        </div>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-base font-bold text-slate-900">Channel performance</h2>
                <p class="text-xs text-slate-400">
                    Followers and recent content across your connected platforms.
                </p>
            </div>
            <button
                type="button"
                :disabled="refreshing"
                class="inline-flex items-center gap-2 rounded-xl bg-white px-4 py-2.5 text-sm font-medium text-slate-600 shadow-sm ring-1 ring-inset ring-slate-300 transition hover:bg-slate-50 disabled:opacity-60"
                @click="refresh"
            >
                <svg
                    class="size-4"
                    :class="{ 'animate-spin': refreshing }"
                    fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                </svg>
                {{ refreshing ? 'Refreshing…' : 'Refresh' }}
            </button>
        </div>

        <div v-if="analyticsAccountCount === 0" class="rounded-2xl border-2 border-dashed border-slate-200 bg-white p-12 text-center">
            <p class="text-3xl">📊</p>
            <h3 class="mt-3 text-lg font-semibold text-slate-900">No accounts yet</h3>
            <p class="mt-1 text-sm text-slate-500">
                Add social accounts in the Account Vault and their performance will show up here.
            </p>
        </div>

        <template v-else>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-2xl bg-brand-600 p-5 text-white shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-brand-200">Total followers</p>
                    <p class="mt-2 text-3xl font-bold">{{ compactNumber(totalFollowers) }}</p>
                    <p class="mt-1 text-xs text-brand-200">
                        across {{ overviewAccounts.length }} account{{ overviewAccounts.length === 1 ? '' : 's' }}
                    </p>
                </div>

                <article
                    v-for="account in overviewAccounts"
                    :key="account.id"
                    class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200"
                >
                    <div class="flex items-start gap-3">
                        <div class="relative flex size-12 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-brand-50 text-xl ring-1 ring-slate-200">
                            <img
                                v-if="account.avatar"
                                :src="account.avatar"
                                :alt="`${account.platform} avatar`"
                                class="size-full object-cover"
                            />
                            <span v-else>{{ platformMeta(account.platform).icon }}</span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-slate-900">{{ account.title || account.handle }}</p>
                            <p class="truncate text-xs text-slate-400">{{ account.handle }}</p>
                        </div>
                        <span
                            class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide"
                            :class="account.live ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'"
                        >
                            {{ account.live ? 'Live' : 'Demo' }}
                        </span>
                    </div>
                    <p class="mt-4 text-2xl font-bold text-slate-900">
                        {{ compactNumber(account.followers) }}
                    </p>
                    <p class="text-xs text-slate-400">followers</p>
                    <a
                        v-if="account.url"
                        :href="account.url"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="mt-3 inline-flex items-center gap-1 text-xs font-medium text-brand-600 hover:text-brand-700"
                    >
                        Open profile
                        <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                        </svg>
                    </a>
                    <p v-if="!account.live && account.note" class="mt-3 text-[11px] leading-snug text-amber-600">
                        {{ account.note }}
                    </p>
                </article>
            </div>

            <div class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                <div class="flex flex-col gap-2 border-b border-slate-100 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900">Recent content</h3>
                        <p class="text-xs text-slate-400">Latest posts with engagement across platforms</p>
                    </div>
                    <span v-if="isDemo" class="text-[11px] text-amber-600">
                        Sample content — connect API keys for real posts.
                    </span>
                </div>

                <div v-if="accounts.analyticsLoading" class="space-y-3 p-6">
                    <div v-for="i in 5" :key="i" class="flex animate-pulse gap-4">
                        <div class="h-20 w-32 shrink-0 rounded-xl bg-slate-100"></div>
                        <div class="flex-1 space-y-2 py-1">
                            <div class="h-3 w-1/2 rounded bg-slate-100"></div>
                            <div class="h-3 w-full rounded bg-slate-100"></div>
                        </div>
                    </div>
                </div>

                <div v-else-if="feed.length === 0" class="p-12 text-center">
                    <p class="text-3xl">🗞️</p>
                    <h3 class="mt-3 text-lg font-semibold text-slate-900">No content found</h3>
                    <p class="mt-1 text-sm text-slate-500">
                        Once your accounts publish posts, they'll appear here with their engagement stats.
                    </p>
                </div>

                <ul v-else class="divide-y divide-slate-100">
                    <li
                        v-for="item in paginatedFeed"
                        :key="item.id"
                        class="flex flex-col gap-4 px-6 py-4 sm:flex-row"
                    >
                        <a
                            :href="item.url || undefined"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="relative flex h-28 w-full shrink-0 items-center justify-center overflow-hidden rounded-xl bg-slate-100 ring-1 ring-inset ring-slate-200 sm:h-20 sm:w-32"
                        >
                            <img
                                v-if="item.thumbnail"
                                :src="item.thumbnail"
                                :alt="item.title"
                                class="h-full w-full object-cover"
                                loading="lazy"
                            />
                            <span v-else class="text-2xl">{{ platformMeta(item.platform).icon }}</span>
                        </a>

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <h4 class="min-w-0 flex-1 truncate font-semibold text-slate-900">{{ item.title }}</h4>
                                <span class="rounded-md bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600">
                                    {{ platformMeta(item.platform).icon }} {{ item.platform }}
                                </span>
                            </div>
                            <p class="mt-1 line-clamp-2 text-sm text-slate-500">{{ item.description || 'No description.' }}</p>
                            <div class="mt-2.5 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-500">
                                <span v-if="item.views !== null && item.views !== undefined" title="Views">
                                    👁 {{ compactNumber(item.views) }}
                                </span>
                                <span v-if="item.likes !== null && item.likes !== undefined" title="Likes">
                                    ❤️ {{ compactNumber(item.likes) }}
                                </span>
                                <span v-if="item.comments !== null && item.comments !== undefined" title="Comments">
                                    💬 {{ compactNumber(item.comments) }}
                                </span>
                                <span v-if="item.published_at" class="text-slate-400">
                                    {{ relativeTime(item.published_at) }}
                                </span>
                                <span
                                    v-if="isDemo"
                                    class="rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-medium text-amber-600"
                                >
                                    sample
                                </span>
                            </div>
                        </div>
                    </li>
                </ul>

                <div v-if="feedPages > 1" class="flex items-center justify-between gap-3 border-t border-slate-100 px-6 py-4">
                    <span class="text-sm text-slate-500">
                        Showing {{ feedRange.start }}–{{ feedRange.end }} of {{ feed.length }}
                    </span>
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            :disabled="feedPage <= 1"
                            class="rounded-xl bg-white px-4 py-2 text-sm font-medium text-slate-600 ring-1 ring-inset ring-slate-300 transition hover:bg-slate-50 disabled:opacity-40"
                            @click="feedPage--"
                        >
                            ← Previous
                        </button>
                        <span class="text-sm text-slate-500">Page {{ feedPage }} of {{ feedPages }}</span>
                        <button
                            type="button"
                            :disabled="feedPage >= feedPages"
                            class="rounded-xl bg-white px-4 py-2 text-sm font-medium text-slate-600 ring-1 ring-inset ring-slate-300 transition hover:bg-slate-50 disabled:opacity-40"
                            @click="feedPage++"
                        >
                            Next →
                        </button>
                    </div>
                </div>
            </div>
        </template>

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
            <div class="flex flex-col gap-3 border-b border-slate-100 p-4 sm:flex-row sm:flex-wrap sm:items-center sm:px-6 sm:py-4">
                <h3 class="mr-auto text-sm font-semibold text-slate-900">Posting history</h3>
                <input
                    v-model="from"
                    type="date"
                    class="w-full rounded-xl border-0 bg-slate-50 px-3 py-2 text-sm shadow-sm ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-brand-500 sm:w-auto"
                    @change="load"
                />
                <input
                    v-model="to"
                    type="date"
                    class="w-full rounded-xl border-0 bg-slate-50 px-3 py-2 text-sm shadow-sm ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-brand-500 sm:w-auto"
                    @change="load"
                />
                <select
                    v-model="platform"
                    class="w-full rounded-xl border-0 bg-slate-50 px-3 py-2 text-sm shadow-sm ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-brand-500 sm:w-auto"
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
                    class="flex flex-col gap-3 px-4 py-4 sm:flex-row sm:items-center sm:gap-4 sm:px-6"
                >
                    <div class="flex items-center justify-between gap-4 sm:w-36 sm:shrink-0 sm:block">
                        <div>
                            <p class="text-sm font-semibold text-slate-900">{{ formatDate(item.date) }}</p>
                            <p class="text-xs text-slate-400">
                                {{ item.count }} post{{ item.count === 1 ? '' : 's' }}
                            </p>
                        </div>
                    </div>
                    <div class="h-2.5 min-w-0 flex-1 overflow-hidden rounded-full bg-slate-100">
                        <div
                            class="h-full rounded-full bg-brand-500"
                            :style="{ width: `${Math.max(6, (item.count / maxCount) * 100)}%` }"
                        ></div>
                    </div>
                    <div class="flex shrink-0 flex-wrap justify-start gap-1.5 sm:justify-end">
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
