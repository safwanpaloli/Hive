<script setup>
import { reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import { extractApiError } from '../utils/errors';
import ToastStack from '../components/ToastStack.vue';

const router = useRouter();
const route = useRoute();
const auth = useAuthStore();

const form = reactive({
    email: 'safwanpaloli7@gmail.com',
    password: 'Safwanpaloli7@6960',
});

const loading = ref(false);
const error = ref('');

async function handleLogin() {
    loading.value = true;
    error.value = '';
    try {
        await auth.login(form);
        router.push(route.query.redirect || { name: 'dashboard' });
    } catch (e) {
        error.value = extractApiError(e, 'Login failed. Please check your credentials.');
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <div class="flex min-h-screen">
        <div class="relative hidden w-1/2 flex-col justify-between overflow-hidden bg-brand-900 p-12 lg:flex">
            <div class="pointer-events-none absolute inset-0 opacity-20">
                <div class="absolute -left-24 top-10 size-80 rounded-full bg-brand-500 blur-3xl"></div>
                <div class="absolute bottom-0 right-0 size-96 rounded-full bg-fuchsia-500 blur-3xl"></div>
            </div>

            <div class="relative flex items-center gap-2.5">
                <div class="flex size-10 items-center justify-center rounded-xl bg-white/10 text-xl">📅</div>
                <p class="text-lg font-bold text-white">ContentVault</p>
            </div>

            <div class="relative space-y-6">
                <p class="text-3xl font-bold leading-tight text-white">
                    Plan. Script.<br />Post. <span class="text-brand-300">Every day.</span>
                </p>
                <p class="max-w-md text-sm leading-relaxed text-brand-200">
                    Your self-hosted social media command center — queue daily posts across every
                    platform, keep your account vault secure, and never miss a scheduled post again.
                </p>
                <div class="flex flex-wrap gap-2">
                    <span class="rounded-full bg-white/10 px-3 py-1 text-xs font-medium text-brand-100">Daily queue</span>
                    <span class="rounded-full bg-white/10 px-3 py-1 text-xs font-medium text-brand-100">Script studio</span>
                    <span class="rounded-full bg-white/10 px-3 py-1 text-xs font-medium text-brand-100">Email reminders</span>
                    <span class="rounded-full bg-white/10 px-3 py-1 text-xs font-medium text-brand-100">Post history</span>
                </div>
            </div>

            <p class="relative text-xs text-brand-300">Self-hosted · Laravel + Vue 3</p>
        </div>

        <div class="flex w-full items-center justify-center bg-slate-50 p-6 lg:w-1/2">
            <div class="w-full max-w-sm">
                <div class="mb-8 lg:hidden">
                    <div class="mb-3 flex items-center gap-2">
                        <div class="flex size-9 items-center justify-center rounded-xl bg-brand-600 text-lg text-white">📅</div>
                        <p class="text-lg font-bold text-slate-900">ContentVault</p>
                    </div>
                </div>

                <h2 class="text-2xl font-bold tracking-tight text-slate-900">Welcome back</h2>
                <p class="mt-1 text-sm text-slate-500">Sign in to your content planning dashboard.</p>

                <form class="mt-8 space-y-5" @submit.prevent="handleLogin">
                    <div>
                        <label for="email" class="mb-1.5 block text-sm font-medium text-slate-700">Email address</label>
                        <input
                            id="email"
                            v-model.trim="form.email"
                            type="email"
                            autocomplete="email"
                            required
                            class="w-full rounded-xl border-0 bg-white px-3.5 py-2.5 text-sm text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-brand-500"
                            placeholder="you@example.com"
                        />
                    </div>

                    <div>
                        <label for="password" class="mb-1.5 block text-sm font-medium text-slate-700">Password</label>
                        <input
                            id="password"
                            v-model="form.password"
                            type="password"
                            autocomplete="current-password"
                            required
                            class="w-full rounded-xl border-0 bg-white px-3.5 py-2.5 text-sm text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-brand-500"
                            placeholder="••••••••"
                        />
                    </div>

                    <div
                        v-if="error"
                        class="rounded-xl bg-rose-50 px-3.5 py-2.5 text-sm font-medium text-rose-700 ring-1 ring-inset ring-rose-200"
                    >
                        {{ error }}
                    </div>

                    <button
                        type="submit"
                        :disabled="loading"
                        class="w-full rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        {{ loading ? 'Signing in…' : 'Sign in' }}
                    </button>
                </form>

                <p class="mt-6 text-center text-xs text-slate-400">
                    Demo credentials are pre-filled for you.
                </p>
            </div>
        </div>

        <ToastStack />
    </div>
</template>
