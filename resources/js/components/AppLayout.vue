<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
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

const sidebarOpen = ref(false);

function onKeydown(event) {
    if (event.key === 'Escape') sidebarOpen.value = false;
}

onMounted(() => {
    document.addEventListener('keydown', onKeydown);
    if (auth.isAuthenticated) {
        notifications.fetchNotifications().catch(() => {});
        posts.fetchToday().catch(() => {});
    }
});

onUnmounted(() => {
    document.removeEventListener('keydown', onKeydown);
});

const navItems = [
    { name: 'dashboard', label: 'Dashboard', icon: '📊' },
    { name: 'today', label: 'Today', icon: '🗓' },
    { name: 'scripts', label: 'Script Studio', icon: '✍️' },
    { name: 'accounts', label: 'Account Vault', icon: '🔐' },
    { name: 'analytics', label: 'Analytics', icon: '📈' },
    { name: 'profile', label: 'Profile', icon: '👤' },
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

const isTodayPage = computed(() => route.name === 'today');

watch(
    () => route.fullPath,
    () => {
        sidebarOpen.value = false;
    },
);

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
        <div
            v-if="sidebarOpen"
            class="fixed inset-0 z-30 bg-slate-900/40 backdrop-blur-sm lg:hidden"
            @click="sidebarOpen = false"
        ></div>

        <aside
            class="fixed inset-y-0 left-0 z-40 flex w-64 flex-col border-r border-slate-200 bg-white transition-transform duration-200 ease-in-out lg:translate-x-0"
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
        >
            <div class="flex items-center gap-2.5 px-6 py-5">
                <div class="flex size-9 items-center justify-center rounded-xl bg-brand-600 text-lg text-white shadow-sm">
                    📅
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-bold tracking-tight text-slate-900">ContentVault</p>
                    <p class="text-[11px] text-slate-500">Social Planner</p>
                </div>
                <button
                    type="button"
                    title="Close menu"
                    class="rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 lg:hidden"
                    @click="sidebarOpen = false"
                >
                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
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
                        v-if="item.name === 'today'"
                        class="ml-auto rounded-full bg-brand-100 px-2 py-0.5 text-[10px] font-semibold text-brand-700"
                    >
                        {{ isTodayPage ? 'now' : '' }}
                    </span>
                </RouterLink>
            </nav>

            <div class="border-t border-slate-100 p-3">
                <RouterLink
                    :to="{ name: 'profile' }"
                    class="flex items-center gap-3 rounded-xl bg-slate-50 px-3 py-2.5 transition hover:bg-slate-100"
                >
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
                        @click.stop="handleLogout"
                    >
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" />
                        </svg>
                    </button>
                </RouterLink>
            </div>
        </aside>

        <main class="flex-1 lg:pl-64">
            <header class="sticky top-0 z-30 border-b border-slate-200 bg-slate-50/80 px-4 py-3 backdrop-blur sm:px-6 lg:px-8 lg:py-4">
                <div class="flex flex-wrap items-center gap-2 sm:gap-3">
                    <button
                        type="button"
                        title="Open menu"
                        class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-white text-slate-600 ring-1 ring-inset ring-slate-200 transition hover:bg-slate-50 lg:hidden"
                        @click="sidebarOpen = true"
                    >
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5" />
                        </svg>
                    </button>
                    <div class="mr-auto min-w-0">
                        <h1 class="truncate text-base font-bold text-slate-900 sm:text-lg">
                            {{ navItems.find((n) => n.name === route.name)?.label || 'Dashboard' }}
                        </h1>
                        <p class="hidden text-xs text-slate-500 sm:block">
                            {{ new Date().toLocaleDateString(undefined, { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' }) }}
                        </p>
                    </div>
                    <div class="flex items-center gap-2 sm:gap-3">
                        <button
                            type="button"
                            title="Toggle browser reminders"
                            class="flex size-9 items-center justify-center rounded-lg bg-white text-base ring-1 ring-inset ring-slate-200 transition hover:bg-slate-50 sm:size-auto sm:px-3 sm:py-2 sm:text-sm sm:font-medium"
                            :class="
                                remindersEnabled
                                    ? 'text-brand-700 ring-brand-200 bg-brand-50'
                                    : 'text-slate-600'
                            "
                            @click="toggleReminders"
                        >
                            <span>🔔</span>
                            <span class="hidden sm:inline sm:ml-2">{{ remindersEnabled ? 'Reminders on' : 'Enable reminders' }}</span>
                        </button>
                        <button
                            v-if="notifications.unread > 0"
                            type="button"
                            title="Mark all notifications as read"
                            class="relative flex size-9 items-center justify-center rounded-lg bg-white text-slate-600 ring-1 ring-inset ring-slate-200 transition hover:bg-slate-50 sm:size-auto sm:gap-2 sm:px-3 sm:py-2 sm:text-sm sm:font-medium"
                            @click="notifications.markAllRead()"
                        >
                            <span>🔔</span>
                            <span class="absolute -right-1 -top-1 flex size-5 items-center justify-center rounded-full bg-rose-500 text-[10px] font-bold text-white sm:static">
                                {{ notifications.unread }}
                            </span>
                            <span class="hidden sm:inline">Mark read</span>
                        </button>
                        <button
                            type="button"
                            class="whitespace-nowrap rounded-lg bg-brand-600 px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700 sm:px-4"
                            @click="isTodayPage ? refreshToday() : router.push({ name: 'scripts', query: { new: '1' } })"
                        >
                            {{ isTodayPage ? '↻ Refresh' : '+ New Script' }}
                        </button>
                    </div>
                </div>
            </header>

            <section class="p-4 sm:p-6 lg:p-8">
                <RouterView />
            </section>
        </main>

        <ToastStack />
    </div>
</template>
