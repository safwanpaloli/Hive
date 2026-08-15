<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import { useNotificationsStore } from '../stores/notifications';
import { usePostsStore } from '../stores/posts';
import { useBrowserNotifications } from '../composables/useBrowserNotifications';
import { useToast } from '../composables/useToast';
import ToastStack from './ToastStack.vue';

const route = useRoute();
const router = useRouter();
const auth = useAuthStore();
const notifications = useNotificationsStore();
const posts = usePostsStore();
const toast = useToast();
const browserNotifications = useBrowserNotifications();

const remindersEnabled = ref(localStorage.getItem('browser_notifications') === '1');

const navItems = [
    { name: 'dashboard', label: 'Today', icon: '🗓' },
    { name: 'scripts', label: 'Script Studio', icon: '✍️' },
    { name: 'accounts', label: 'Account Vault', icon: '🔐' },
    { name: 'analytics', label: 'Analytics', icon: '📈' },
];

const initials = computed(() => {
    const name = auth.user?.name || 'U';
    return name
        .split(' ')
        .map((p) => p[0])
        .slice(0, 2)
        .join('')
        .toUpperCase();
});

const isTodayPage = computed(() => route.name === 'dashboard');

onMounted(() => {
    if (auth.isAuthenticated) {
        notifications.fetchNotifications().catch(() => {});
        posts.fetchToday().catch(() => {});
    }
});

watch(
    () => posts.today,
    (today) => {
        if (today?.total != null && today.total > 0 && remindersEnabled.value) {
            browserNotifications.remindAboutToday();
        }
    },
);

async function toggleReminders() {
    if (remindersEnabled.value) {
        remindersEnabled.value = false;
        localStorage.setItem('browser_notifications', '0');
        toast.info('Browser reminders disabled.');
        return;
    }

    const permission = await browserNotifications.requestPermission();
    if (permission === 'granted') {
        remindersEnabled.value = true;
        localStorage.setItem('browser_notifications', '1');
        toast.success("Browser reminders enabled. You'll be nudged for today's queue.");
        browserNotifications.remindAboutToday();
    } else {
        toast.error('Notification permission denied by the browser.');
    }
}

async function handleLogout() {
    await auth.logout();
    router.push({ name: 'login' });
}

function refreshToday() {
    window.location.reload();
}
</script>

<template>
    <div class="flex min-h-screen">
        <aside
            class="fixed inset-y-0 left-0 z-40 flex w-64 flex-col border-r border-slate-200 bg-white"
        >
            <div class="flex items-center gap-2.5 px-6 py-5">
                <div class="flex size-9 items-center justify-center rounded-xl bg-brand-600 text-lg text-white shadow-sm">
                    📅
                </div>
                <div>
                    <p class="text-sm font-bold tracking-tight text-slate-900">ContentVault</p>
                    <p class="text-[11px] text-slate-500">Social Planner</p>
                </div>
            </div>

            <nav class="mt-2 flex-1 space-y-1 px-3">
                <RouterLink
                    v-for="item in navItems"
                    :key="item.name"
                    :to="{ name: item.name }"
                    class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition"
                    :class="
                        route.name === item.name
                            ? 'bg-brand-50 text-brand-700'
                            : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'
                    "
                >
                    <span class="text-base leading-none">{{ item.icon }}</span>
                    {{ item.label }}
                    <span
                        v-if="item.name === 'dashboard'"
                        class="ml-auto rounded-full bg-brand-100 px-2 py-0.5 text-[10px] font-semibold text-brand-700"
                    >
                        {{ isTodayPage ? 'now' : '' }}
                    </span>
                </RouterLink>
            </nav>

            <div class="border-t border-slate-100 p-3">
                <div class="flex items-center gap-3 rounded-xl bg-slate-50 px-3 py-2.5">
                    <div class="flex size-9 shrink-0 items-center justify-center rounded-full bg-brand-600 text-xs font-bold text-white">
                        {{ initials }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold text-slate-900">{{ auth.user?.name }}</p>
                        <p class="truncate text-xs text-slate-500">{{ auth.user?.email }}</p>
                    </div>
                    <button
                        type="button"
                        title="Sign out"
                        class="rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-200 hover:text-slate-700"
                        @click="handleLogout"
                    >
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" />
                        </svg>
                    </button>
                </div>
            </div>
        </aside>

        <main class="ml-64 flex-1">
            <header class="sticky top-0 z-30 border-b border-slate-200 bg-slate-50/80 px-8 py-4 backdrop-blur">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-lg font-bold text-slate-900">
                            {{ navItems.find((n) => n.name === route.name)?.label || 'Dashboard' }}
                        </h1>
                        <p class="text-xs text-slate-500">
                            {{ new Date().toLocaleDateString(undefined, { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' }) }}
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        <button
                            type="button"
                            title="Toggle browser reminders"
                            class="relative flex items-center gap-2 rounded-lg bg-white px-3 py-2 text-sm font-medium ring-1 ring-slate-200 transition hover:bg-slate-50"
                            :class="
                                remindersEnabled
                                    ? 'text-brand-700 ring-brand-200 bg-brand-50'
                                    : 'text-slate-600'
                            "
                            @click="toggleReminders"
                        >
                            <span>🔔</span>
                            <span class="hidden sm:inline">{{ remindersEnabled ? 'Reminders on' : 'Enable reminders' }}</span>
                        </button>
                        <button
                            v-if="notifications.unread > 0"
                            type="button"
                            class="relative flex items-center gap-2 rounded-lg bg-white px-3 py-2 text-sm font-medium text-slate-600 ring-1 ring-slate-200 transition hover:bg-slate-50"
                            @click="notifications.markAllRead()"
                        >
                            <span>🔔</span>
                            <span class="flex size-5 items-center justify-center rounded-full bg-rose-500 text-[10px] font-bold text-white">
                                {{ notifications.unread }}
                            </span>
                            Mark read
                        </button>
                        <button
                            type="button"
                            class="flex items-center gap-2 rounded-lg bg-brand-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700"
                            @click="isTodayPage ? refreshToday() : router.push({ name: 'scripts', query: { new: '1' } })"
                        >
                            {{ isTodayPage ? '↻ Refresh' : '+ New Script' }}
                        </button>
                    </div>
                </div>
            </header>

            <section class="p-8">
                <RouterView />
            </section>
        </main>

        <ToastStack />
    </div>
</template>
