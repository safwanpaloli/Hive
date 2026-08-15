<script setup>
import { computed, reactive, ref } from 'vue';
import { useAccountsStore } from '../stores/accounts';
import { useToast } from '../composables/useToast';
import { humanizePlatform } from '../utils/format';

const props = defineProps({
    account: { type: Object, default: null },
});

const emit = defineEmits(['close', 'saved']);

const accounts = useAccountsStore();
const toast = useToast();

const saving = ref(false);
const errors = ref({});
const avatarFile = ref(null);
const avatarPreview = ref('');
const credential = ref('');

const form = reactive({
    platform_name: '',
    handle: '',
    profile_url: '',
    account_type: '',
    notes: '',
});

const credentialField = computed(() => {
    const platform = form.platform_name.trim().toLowerCase();
    if (platform === 'youtube') return { key: 'api_key', label: 'YouTube API key', placeholder: 'AIza…' };
    if (platform === 'facebook') return { key: 'access_token', label: 'Facebook page access token', placeholder: 'EAAG…' };
    if (platform === 'instagram') return { key: 'access_token', label: 'Instagram access token', placeholder: 'EAAG…' };
    return { key: 'access_token', label: 'Access token', placeholder: 'Paste token…' };
});

const credentialHint = computed(() => {
    const platform = form.platform_name.trim().toLowerCase();
    if (platform === 'youtube') {
        return 'Google API key with the YouTube Data API v3 enabled — used for live analytics. Leave empty to clear.';
    }
    if (platform === 'facebook' || platform === 'instagram') {
        return 'Meta Graph API access token (starts with EAAG…) from a Facebook app connected to your page/Instagram Business account. Leave empty to clear.';
    }
    return 'Used for live analytics on this platform. Leave empty to clear.';
});

const isEditing = ref(false);

function reset() {
    isEditing.value = Boolean(props.account?.id);
    if (props.account) {
        form.platform_name = props.account.platform_name || '';
        form.handle = props.account.handle || '';
        form.profile_url = props.account.profile_url || '';
        form.account_type = props.account.account_type || '';
        form.notes = props.account.notes || '';
        avatarPreview.value = props.account.avatar_url || '';
        credential.value = props.account.credentials?.[credentialField.value.key] || '';
    } else {
        form.platform_name = '';
        form.handle = '';
        form.profile_url = '';
        form.account_type = '';
        form.notes = '';
        avatarPreview.value = '';
    }
    credential.value = '';
    avatarFile.value = null;
    errors.value = {};
}

reset();

const platformPresets = ['Facebook', 'Instagram', 'X (Twitter)', 'LinkedIn', 'YouTube', 'TikTok', 'Pinterest', 'Threads'];

const previewSrc = computed(() => avatarPreview.value || null);

const fallbackIcon = computed(() => humanizePlatform(form.platform_name || 'Social').icon);

function onAvatarChange(event) {
    const file = event.target.files?.[0];
    if (!file) return;
    if (!file.type.startsWith('image/')) {
        toast.error('Please choose an image file.');
        event.target.value = '';
        return;
    }
    avatarFile.value = file;
    avatarPreview.value = URL.createObjectURL(file);
}

function clearAvatar() {
    avatarFile.value = null;
    avatarPreview.value = '';
}

function buildPayload() {
    const credentialsPayload = credential.value.trim()
        ? { [credentialField.value.key]: credential.value.trim() }
        : null;

    if (avatarFile.value) {
        const payload = new FormData();
        payload.append('platform_name', form.platform_name);
        payload.append('handle', form.handle);
        payload.append('profile_url', form.profile_url || '');
        payload.append('account_type', form.account_type || '');
        payload.append('notes', form.notes || '');
        payload.append('avatar', avatarFile.value);
        if (credentialsPayload) {
            payload.append(`credentials[${credentialField.value.key}]`, credentialsPayload[credentialField.value.key]);
        }
        return payload;
    }

    // No new file: keep the existing avatar unless the user cleared it.
    return { ...form, avatar_url: avatarPreview.value || '', credentials: credentialsPayload };
}

async function save() {
    saving.value = true;
    errors.value = {};
    try {
        if (isEditing.value) {
            await accounts.updateAccount(props.account.id, buildPayload());
            toast.success('Account updated.');
        } else {
            await accounts.createAccount(buildPayload());
            toast.success('Account added to vault.');
        }
        emit('saved');
        emit('close');
    } catch (e) {
        errors.value = e.response?.data?.errors || {};
        toast.error('Please review the highlighted fields.');
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <form class="space-y-5" @submit.prevent="save">
        <div class="flex items-center gap-4">
            <div class="relative flex size-16 shrink-0 items-center justify-center overflow-hidden rounded-full bg-slate-100 ring-2 ring-slate-200">
                <img
                    v-if="previewSrc"
                    :src="previewSrc"
                    alt="Profile preview"
                    class="size-full object-cover"
                />
                <span v-else class="text-2xl leading-none">{{ fallbackIcon }}</span>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-sm font-medium text-slate-700">Profile picture</p>
                <p class="text-xs text-slate-400">PNG, JPG, WebP or GIF · up to 2 MB</p>
                <div class="mt-2 flex flex-wrap items-center gap-2">
                    <label
                        class="cursor-pointer rounded-lg bg-white px-3 py-1.5 text-xs font-semibold text-brand-700 ring-1 ring-inset ring-brand-200 transition hover:bg-brand-50"
                    >
                        Upload
                        <input
                            type="file"
                            accept="image/png,image/jpeg,image/webp,image/gif"
                            class="hidden"
                            @change="onAvatarChange"
                        />
                    </label>
                    <button
                        v-if="avatarPreview"
                        type="button"
                        class="rounded-lg px-3 py-1.5 text-xs font-medium text-rose-600 transition hover:bg-rose-50"
                        @click="clearAvatar"
                    >
                        Remove
                    </button>
                </div>
                <p v-if="errors.avatar" class="mt-1 text-xs text-rose-600">{{ errors.avatar[0] }}</p>
            </div>
        </div>

        <div>
            <label for="acc-platform" class="mb-1.5 block text-sm font-medium text-slate-700">Platform</label>
            <input
                id="acc-platform"
                v-model.trim="form.platform_name"
                type="text"
                list="platform-presets"
                required
                class="w-full rounded-xl border-0 bg-white px-3.5 py-2.5 text-sm shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-500"
                placeholder="e.g. Instagram"
            />
            <datalist id="platform-presets">
                <option v-for="p in platformPresets" :key="p" :value="p">{{ p }}</option>
            </datalist>
            <p v-if="errors.platform_name" class="mt-1 text-xs text-rose-600">{{ errors.platform_name[0] }}</p>
        </div>

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
            <div>
                <label for="acc-handle" class="mb-1.5 block text-sm font-medium text-slate-700">Handle / Username</label>
                <input
                    id="acc-handle"
                    v-model.trim="form.handle"
                    type="text"
                    required
                    class="w-full rounded-xl border-0 bg-white px-3.5 py-2.5 text-sm shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-500"
                    placeholder="@yourhandle"
                />
                <p v-if="errors.handle" class="mt-1 text-xs text-rose-600">{{ errors.handle[0] }}</p>
            </div>
            <div>
                <label for="acc-type" class="mb-1.5 block text-sm font-medium text-slate-700">Account type</label>
                <select
                    id="acc-type"
                    v-model="form.account_type"
                    class="w-full rounded-xl border-0 bg-white px-3.5 py-2.5 text-sm shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-500"
                >
                    <option value="">—</option>
                    <option value="Personal">Personal</option>
                    <option value="Business">Business</option>
                    <option value="Creator">Creator</option>
                </select>
            </div>
        </div>

        <div>
            <label for="acc-url" class="mb-1.5 block text-sm font-medium text-slate-700">Direct profile link</label>
            <input
                id="acc-url"
                v-model.trim="form.profile_url"
                type="url"
                class="w-full rounded-xl border-0 bg-white px-3.5 py-2.5 text-sm shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-500"
                placeholder="https://instagram.com/yourhandle"
            />
            <p v-if="errors.profile_url" class="mt-1 text-xs text-rose-600">{{ errors.profile_url[0] }}</p>
        </div>

        <div>
            <label for="acc-credential" class="mb-1.5 block text-sm font-medium text-slate-700">
                {{ credentialField.label }}
                <span class="font-normal text-slate-400">(optional)</span>
            </label>
            <input
                id="acc-credential"
                v-model="credential"
                type="password"
                autocomplete="off"
                :placeholder="credentialField.placeholder"
                class="w-full rounded-xl border-0 bg-white px-3.5 py-2.5 text-sm shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-500"
            />
            <p class="mt-1 text-xs text-slate-400">
                {{ credentialHint }}
            </p>
        </div>

        <div>
            <label for="acc-notes" class="mb-1.5 block text-sm font-medium text-slate-700">Notes / credentials info</label>
            <textarea
                id="acc-notes"
                v-model="form.notes"
                rows="3"
                class="w-full resize-y rounded-xl border-0 bg-white px-3.5 py-2.5 text-sm shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-500"
                placeholder="Login email, app passwords, team permissions…"
            ></textarea>
        </div>

        <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-5">
            <button
                type="button"
                class="rounded-xl px-4 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-100"
                @click="emit('close')"
            >
                Cancel
            </button>
            <button
                type="submit"
                :disabled="saving"
                class="rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700 disabled:opacity-60"
            >
                {{ saving ? 'Saving…' : isEditing ? 'Save changes' : 'Add account' }}
            </button>
        </div>
    </form>
</template>
