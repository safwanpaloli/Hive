import { defineStore } from 'pinia';
import client from '../api/client';

function multipartConfig(payload) {
    // Let the browser set the multipart boundary automatically for FormData.
    if (payload instanceof FormData) {
        return { headers: { 'Content-Type': undefined } };
    }
    return {};
}

export const useAccountsStore = defineStore('accounts', {
    state: () => ({
        accounts: [],
        loading: false,
        error: null,
    }),

    getters: {
        platformNames: (state) => [...new Set(state.accounts.map((a) => a.platform_name))],
        byId: (state) => (id) => state.accounts.find((a) => a.id === Number(id)) || null,
    },

    actions: {
        async fetchAccounts() {
            this.loading = true;
            this.error = null;
            try {
                const { data } = await client.get('/v1/social-accounts');
                this.accounts = data.accounts;
            } catch (e) {
                this.error = e.response?.data?.message || 'Failed to load accounts.';
                throw e;
            } finally {
                this.loading = false;
            }
        },

        async createAccount(payload) {
            const { data } = await client.post('/v1/social-accounts', payload, multipartConfig(payload));
            this.accounts.push(data.account);
            return data.account;
        },

        async updateAccount(id, payload) {
            // The PHP built-in server only parses multipart bodies for POST,
            // so FormData updates use POST + `_method` spoofing (Laravel pattern).
            if (payload instanceof FormData) {
                payload.append('_method', 'PUT');
                const { data } = await client.post(`/v1/social-accounts/${id}`, payload, multipartConfig(payload));
                const idx = this.accounts.findIndex((a) => a.id === id);
                if (idx !== -1) this.accounts[idx] = data.account;
                return data.account;
            }

            const { data } = await client.put(`/v1/social-accounts/${id}`, payload, multipartConfig(payload));
            const idx = this.accounts.findIndex((a) => a.id === id);
            if (idx !== -1) this.accounts[idx] = data.account;
            return data.account;
        },

        async deleteAccount(id) {
            await client.delete(`/v1/social-accounts/${id}`);
            this.accounts = this.accounts.filter((a) => a.id !== id);
        },
    },
});
