<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useAuthStore } from '../stores/auth';
import { useToast } from '../composables/useToast';
import { formatDateTime } from '../utils/format';

const auth = useAuthStore();
const toast = useToast();

const loading = ref(true);
const savingDetails = ref(false);
const savingPassword = ref(false);

const detailsForm = reactive({ name: '', email: '' });
const passwordForm = reactive({ current_password: '', password: '', password_confirmation: '' });

const detailsErrors = ref({});
const passwordErrors = ref({});

const initials = computed(() => {
    const name = auth.user?.name || 'U';
    return name
        .split(' ')
        .map((p) => p[0])
        .slice(0, 2)
        .join('')
        .toUpperCase();
});

const memberSince = computed(() => formatDateTime(auth.user?.created_at));
const lastUpdated = computed(() => formatDateTime(auth.user?.updated_at));
const verified = computed(() => Boolean(auth.user?.email_verified_at));

onMounted(async () => {
    try {
        await auth.fetchProfile();
        syncDetailsForm();
    } catch {
        toast.error('Could not load profile.');
    } finally {
        loading.value = false;
    }
});

function syncDetailsForm() {
    detailsForm.name = auth.user?.name || '';
    detailsForm.email = auth.user?.email || '';
}

async function saveDetails() {
    savingDetails.value = true;
    detailsErrors.value = {};
    try {
        await auth.updateProfile({ ...detailsForm });
        toast.success('Profile updated.');
    } catch (e) {
        detailsErrors.value = e.response?.data?.errors || {};
        toast.error('Please review the highlighted fields.');
    } finally {
        savingDetails.value = false;
    }
}

async function savePassword() {
    savingPassword.value = true;
    passwordErrors.value = {};
    try {
        await auth.changePassword({ ...passwordForm });
        toast.success('Password changed.');
        passwordForm.current_password = '';
        passwordForm.password = '';
        passwordForm.password_confirmation = '';
    } catch (e) {
        passwordErrors.value = e.response?.data?.errors || {};
        toast.error('Please review the highlighted fields.');
    } finally {
        savingPassword.value = false;
    }
}
</script>

<template>
    <div class="space-y-6">
        <div v-if="loading" class="grid grid-cols-1 gap-4 lg:grid-cols-3">
            <div v-for="i in 3" :key="i" class="animate-pulse rounded-2xl bg-white p-5 ring-1 ring-slate-200">
                <div class="h-4 w-1/2 rounded bg-slate-200"></div>
                <div class="mt-3 h-3 w-full rounded bg-slate-100"></div>
            </div>
        </div>

        <template v-else>
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                    <div class="flex size-16 shrink-0 items-center justify-center rounded-2xl bg-brand-600 text-xl font-bold text-white shadow-sm">
                        {{ initials }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="text-lg font-bold text-slate-900">{{ auth.user?.name }}</h2>
                            <span
                                class="rounded-full px-2.5 py-0.5 text-[11px] font-semibold"
                                :class="verified ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'"
                            >
                                {{ verified ? 'Email verified' : 'Email not verified' }}
                            </span>
                        </div>
                        <p class="text-sm text-slate-500">{{ auth.user?.email }}</p>
                        <p class="mt-1 text-xs text-slate-400">Member since {{ memberSince }}</p>
                    </div>
                </div>

                <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div class="rounded-xl bg-slate-50 px-4 py-3 ring-1 ring-inset ring-slate-100">
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Social accounts</p>
                        <p class="mt-1 text-2xl font-bold text-slate-900">{{ auth.user?.social_accounts_count ?? 0 }}</p>
                    </div>
                    <div class="rounded-xl bg-slate-50 px-4 py-3 ring-1 ring-inset ring-slate-100">
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Scripts written</p>
                        <p class="mt-1 text-2xl font-bold text-slate-900">{{ auth.user?.posts_count ?? 0 }}</p>
                    </div>
                    <div class="rounded-xl bg-slate-50 px-4 py-3 ring-1 ring-inset ring-slate-100">
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Last updated</p>
                        <p class="mt-1 truncate text-sm font-semibold text-slate-900">{{ lastUpdated }}</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <div class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                    <div class="border-b border-slate-100 px-6 py-4">
                        <h3 class="text-sm font-semibold text-slate-900">Account details</h3>
                        <p class="text-xs text-slate-400">Update your name and sign-in email.</p>
                    </div>
                    <form class="space-y-5 p-6" @submit.prevent="saveDetails">
                        <div>
                            <label for="pf-name" class="mb-1.5 block text-sm font-medium text-slate-700">Full name</label>
                            <input
                                id="pf-name"
                                v-model.trim="detailsForm.name"
                                type="text"
                                required
                                class="w-full rounded-xl border-0 bg-white px-3.5 py-2.5 text-sm shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-500"
                            />
                            <p v-if="detailsErrors.name" class="mt-1 text-xs text-rose-600">{{ detailsErrors.name[0] }}</p>
                        </div>
                        <div>
                            <label for="pf-email" class="mb-1.5 block text-sm font-medium text-slate-700">Email</label>
                            <input
                                id="pf-email"
                                v-model.trim="detailsForm.email"
                                type="email"
                                required
                                class="w-full rounded-xl border-0 bg-white px-3.5 py-2.5 text-sm shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-500"
                            />
                            <p v-if="detailsErrors.email" class="mt-1 text-xs text-rose-600">{{ detailsErrors.email[0] }}</p>
                        </div>
                        <div class="flex justify-end">
                            <button
                                type="submit"
                                :disabled="savingDetails"
                                class="rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700 disabled:opacity-60"
                            >
                                {{ savingDetails ? 'Saving…' : 'Save changes' }}
                            </button>
                        </div>
                    </form>
                </div>

                <div class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                    <div class="border-b border-slate-100 px-6 py-4">
                        <h3 class="text-sm font-semibold text-slate-900">Change password</h3>
                        <p class="text-xs text-slate-400">Use at least 8 characters. You'll stay signed in here.</p>
                    </div>
                    <form class="space-y-5 p-6" @submit.prevent="savePassword">
                        <div>
                            <label for="pf-current" class="mb-1.5 block text-sm font-medium text-slate-700">Current password</label>
                            <input
                                id="pf-current"
                                v-model="passwordForm.current_password"
                                type="password"
                                autocomplete="current-password"
                                required
                                class="w-full rounded-xl border-0 bg-white px-3.5 py-2.5 text-sm shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-500"
                            />
                            <p v-if="passwordErrors.current_password" class="mt-1 text-xs text-rose-600">{{ passwordErrors.current_password[0] }}</p>
                        </div>
                        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                            <div>
                                <label for="pf-new" class="mb-1.5 block text-sm font-medium text-slate-700">New password</label>
                                <input
                                    id="pf-new"
                                    v-model="passwordForm.password"
                                    type="password"
                                    autocomplete="new-password"
                                    required
                                    minlength="8"
                                    class="w-full rounded-xl border-0 bg-white px-3.5 py-2.5 text-sm shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-500"
                                />
                                <p v-if="passwordErrors.password" class="mt-1 text-xs text-rose-600">{{ passwordErrors.password[0] }}</p>
                            </div>
                            <div>
                                <label for="pf-confirm" class="mb-1.5 block text-sm font-medium text-slate-700">Confirm new password</label>
                                <input
                                    id="pf-confirm"
                                    v-model="passwordForm.password_confirmation"
                                    type="password"
                                    autocomplete="new-password"
                                    required
                                    minlength="8"
                                    class="w-full rounded-xl border-0 bg-white px-3.5 py-2.5 text-sm shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-500"
                                />
                            </div>
                        </div>
                        <div class="flex justify-end">
                            <button
                                type="submit"
                                :disabled="savingPassword"
                                class="rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700 disabled:opacity-60"
                            >
                                {{ savingPassword ? 'Updating…' : 'Update password' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </template>
    </div>
</template>
