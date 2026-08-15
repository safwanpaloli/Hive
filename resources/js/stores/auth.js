import { defineStore } from 'pinia';
import client from '../api/client';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        token: localStorage.getItem('auth_token') || null,
        user: JSON.parse(localStorage.getItem('auth_user') || 'null'),
    }),

    getters: {
        isAuthenticated: (state) => Boolean(state.token),
    },

    actions: {
        async login(credentials) {
            const { data } = await client.post('/v1/login', credentials);
            this.token = data.token;
            this.user = data.user;
            localStorage.setItem('auth_token', data.token);
            localStorage.setItem('auth_user', JSON.stringify(data.user));
            return data;
        },

        async logout(remote = true) {
            if (remote && this.token) {
                try {
                    await client.post('/v1/logout');
                } catch {
                    // ignore network errors on logout
                }
            }
            this.token = null;
            this.user = null;
            localStorage.removeItem('auth_token');
            localStorage.removeItem('auth_user');
        },

        async fetchMe() {
            const { data } = await client.get('/v1/me');
            this.user = data.user;
            localStorage.setItem('auth_user', JSON.stringify(data.user));
        },

        async fetchProfile() {
            const { data } = await client.get('/v1/profile');
            this.user = data.user;
            localStorage.setItem('auth_user', JSON.stringify(data.user));
            return data.user;
        },

        async updateProfile(payload) {
            const { data } = await client.put('/v1/profile', payload);
            this.user = data.user;
            localStorage.setItem('auth_user', JSON.stringify(data.user));
            return data.user;
        },

        async changePassword(payload) {
            await client.put('/v1/profile/password', payload);
        },
    },
});
