<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { usePostsStore } from '../stores/posts';
import { useAuthStore } from '../stores/auth';
import StatusBadge from '../components/StatusBadge.vue';
import PlatformBadge from '../components/PlatformBadge.vue';
import { formatTime, relativeTime } from '../utils/format';
import { useToast } from '../composables/useToast';

const router = useRouter();
const posts = usePostsStore();
const auth = useAuthStore();
const toast = useToast();

const savingId = ref(null);

const greeting = computed(() => {
    const hour = new Date().getHours();
    const name = auth.user?.name?.split(' ')[0] || 'there';
    if (hour < 12) return `Good morning, ${name}`;
    if (hour < 17) return `Good afternoon, ${name}`;
    return `Good evening, ${name}`;
});

const progress = computed(() => {
    if (!posts.today.total) return 0;
    return Math.round((posts.today.posted / posts.today.total) * 100);
});

const pendingPosts = computed(() =>
    posts.today.posts.filter((p) => p.status === 'scheduled'),
);
const finishedPosts = computed(() =>
    posts.today.posts.filter((p) => p.status !== 'scheduled'),
);

const sortedPosts = computed(() =>
    [...posts.today.posts].sort(
        (a, b) => new Date(a.scheduled_at) - new Date(b.scheduled_at),
    ),
);

onMounted(() => {
    posts.fetchToday().catch(() => {});
});

async function markPosted(post) {
    savingId.value = post.id;
    try {
        await posts.updateStatus(post.id, 'posted');
        toast.success(`Marked as posted — "${post.title}"`);
    } catch (e) {
        toast.error('Could not update status.');
    } finally {
        savingId.value = null;
    }
}

async function skipPost(post) {
    savingId.value = post.id;
    try {
        await posts.updateStatus(post.id, 'skipped');
        toast.info('Post skipped.');
    } catch {
        toast.error('Could not update status.');
    } finally {
        savingId.value = null;
    }
}

function openEditor(post) {
    router.push({ name: 'scripts', query: { edit: post.id } });
}
</script>

<template>
    <div class="space-y-6">
        <div class="rounded-2xl bg-brand-700 p-6 text-white shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-bold tracking-tight">{{ greeting }}</h2>
                    <p class="mt-1 text-sm text-brand-100">
                        You have
                        <span class="font-semibold text-white">{{ posts.today.pending }}</span>
                        post(s) pending and
                        <span class="font-semibold text-white">{{ posts.today.posted }}</span>
                        marked as posted today.
                    </p>
                </div>
                <div class="min-w-44">
                    <div class="flex items-end justify-between text-xs font-medium">
                        <span class="text-brand-100">Today's progress</span>
                        <span>{{ progress }}%</span>
                    </div>
                    <div class="mt-2 h-2.5 overflow-hidden rounded-full bg-white/20">
                        <div
                            class="h-full rounded-full bg-white transition-all duration-500"
                            :style="{ width: `${progress}%` }"
                        ></div>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="posts.loading" class="space-y-3">
            <div v-for="i in 3" :key="i" class="animate-pulse rounded-2xl bg-white p-5 ring-1 ring-slate-200">
                <div class="h-4 w-1/3 rounded bg-slate-200"></div>
                <div class="mt-3 h-3 w-full rounded bg-slate-100"></div>
            </div>
        </div>

        <div v-else-if="posts.today.total === 0" class="rounded-2xl border-2 border-dashed border-slate-200 bg-white p-12 text-center">
            <p class="text-3xl">🌤</p>
            <h3 class="mt-3 text-lg font-semibold text-slate-900">Nothing scheduled for today</h3>
            <p class="mt-1 text-sm text-slate-500">
                A clean slate. Draft a new script to plan your next post.
            </p>
            <button
                type="button"
                class="mt-5 inline-flex items-center gap-2 rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700"
                @click="router.push({ name: 'scripts', query: { new: '1' } })"
            >
                + Create a script
            </button>
        </div>

        <div v-else class="space-y-4">
            <div class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                <div class="border-b border-slate-100 px-6 py-4">
                    <h3 class="text-sm font-semibold text-slate-900">Your queue · {{ sortedPosts.length }}</h3>
                </div>

                <ul class="divide-y divide-slate-100">
                    <li
                        v-for="post in sortedPosts"
                        :key="post.id"
                        class="group flex flex-col gap-3 px-6 py-4 transition hover:bg-slate-50 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="text-sm font-semibold text-slate-900">{{ post.title }}</span>
                                <StatusBadge :status="post.status" />
                            </div>
                            <p class="mt-1 line-clamp-1 text-sm text-slate-500">{{ post.script_body }}</p>
                            <div class="mt-2 flex flex-wrap items-center gap-2">
                                <PlatformBadge
                                    v-for="platform in post.platforms"
                                    :key="platform.id"
                                    :platform="platform.platform_name"
                                />
                                <span class="text-xs text-slate-400">
                                    {{ formatTime(post.scheduled_at) }} · {{ relativeTime(post.scheduled_at) }}
                                </span>
                            </div>
                        </div>

                        <div class="flex shrink-0 items-center gap-2">
                            <button
                                type="button"
                                class="rounded-lg px-3 py-1.5 text-xs font-medium text-slate-500 transition hover:bg-slate-100 hover:text-slate-700"
                                @click="openEditor(post)"
                            >
                                Edit
                            </button>
                            <button
                                type="button"
                                class="rounded-lg px-3 py-1.5 text-xs font-medium text-amber-600 transition hover:bg-amber-50"
                                @click="skipPost(post)"
                            >
                                Skip
                            </button>
                            <button
                                type="button"
                                :disabled="savingId === post.id"
                                class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-emerald-700 disabled:opacity-60"
                                @click="markPosted(post)"
                            >
                                <span v-if="savingId === post.id">…</span>
                                <span v-else>✓</span>
                                Mark posted
                            </button>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</template>
