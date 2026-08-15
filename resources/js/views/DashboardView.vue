<script setup>
import { computed, onMounted } from 'vue';
import { usePostsStore } from '../stores/posts';
import { useAuthStore } from '../stores/auth';

const posts = usePostsStore();
const auth = useAuthStore();

const greeting = computed(() => {
    const hour = new Date().getHours();
    const name = auth.user?.name?.split(' ')[0] || 'there';
    if (hour < 12) return `Good morning, ${name}`;
    if (hour < 17) return `Good afternoon, ${name}`;
    return `Good evening, ${name}`;
});

const platformTotals = computed(() => {
    if (!posts.today.platform_totals) return [];
    return Object.keys(posts.today.platform_totals).sort().map((name) => ({
        name,
        count: posts.today.platform_totals[name],
    }));
});

const totalAllTime = computed(() => {
    return platformTotals.value.reduce((sum, p) => sum + p.count, 0);
});

onMounted(() => {
    if (!posts.today.platform_totals) {
        posts.fetchToday().catch(() => {});
    }
});
</script>

<template>
    <div class="space-y-6">
        <div class="rounded-2xl bg-brand-700 p-5 text-white shadow-sm sm:p-6">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-bold tracking-tight">{{ greeting }}</h2>
                    <p class="mt-1 text-sm text-brand-100">
                        Welcome to your overall dashboard. Here is your all-time content summary.
                    </p>
                </div>
            </div>
        </div>

        <div>
            <h3 class="mb-4 text-base font-bold text-slate-900">All-Time Platform Statistics</h3>
            
            <div v-if="platformTotals.length > 0" class="grid grid-cols-2 gap-4 sm:grid-cols-4 lg:grid-cols-5">
                <div class="rounded-2xl bg-slate-900 px-4 py-3 shadow-sm ring-1 ring-slate-800">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Total Posts</p>
                    <p class="mt-1 text-2xl font-bold text-white">{{ totalAllTime }}</p>
                </div>
                
                <div v-for="stat in platformTotals" :key="stat.name" class="rounded-2xl bg-white px-4 py-3 shadow-sm ring-1 ring-slate-200">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">{{ stat.name }}</p>
                    <p class="mt-1 text-2xl font-bold text-slate-900">{{ stat.count }}</p>
                </div>
            </div>
            
            <div v-else-if="posts.loading" class="grid grid-cols-2 gap-4 sm:grid-cols-4 lg:grid-cols-5">
                <div v-for="i in 4" :key="i" class="animate-pulse rounded-2xl bg-white p-4 ring-1 ring-slate-200">
                    <div class="h-3 w-16 rounded bg-slate-200"></div>
                    <div class="mt-2 h-6 w-12 rounded bg-slate-200"></div>
                </div>
            </div>

            <div v-else class="rounded-2xl border-2 border-dashed border-slate-200 bg-white p-12 text-center">
                <p class="text-3xl">📊</p>
                <h3 class="mt-3 text-lg font-semibold text-slate-900">No data available yet</h3>
                <p class="mt-1 text-sm text-slate-500">
                    Once you start scheduling and publishing posts to platforms, your all-time stats will appear here.
                </p>
            </div>
        </div>
    </div>
</template>
