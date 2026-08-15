import { defineStore } from 'pinia';
import client from '../api/client';

export const usePostsStore = defineStore('posts', {
    state: () => ({
        today: { date: null, total: 0, posted: 0, pending: 0, posts: [] },
        posts: [],
        pagination: { current_page: 1, last_page: 1, total: 0, per_page: 15 },
        filters: { status: '', from: '', to: '', q: '', platform: '', date: '' },
        history: { stats: {}, total: 0 },
        loading: false,
        error: null,
    }),

    actions: {
        async fetchToday() {
            this.loading = true;
            this.error = null;
            try {
                const { data } = await client.get('/v1/posts/today');
                this.today = data;
            } catch (e) {
                this.error = e.response?.data?.message || 'Failed to load today\'s queue.';
                throw e;
            } finally {
                this.loading = false;
            }
        },

        async fetchPosts(params = {}) {
            this.loading = true;
            this.error = null;
            try {
                const { data } = await client.get('/v1/posts', {
                    params: { ...this.filters, ...params },
                });
                this.posts = data.data;
                this.pagination = {
                    current_page: data.current_page,
                    last_page: data.last_page,
                    total: data.total,
                    per_page: data.per_page,
                };
            } catch (e) {
                this.error = e.response?.data?.message || 'Failed to load posts.';
                throw e;
            } finally {
                this.loading = false;
            }
        },

        async fetchHistory(params = {}) {
            const { data } = await client.get('/v1/posts/history', { params });
            this.history = data;
            return data;
        },

        async createPost(payload) {
            const { data } = await client.post('/v1/posts', payload);
            this.posts.unshift(data.post);
            return data.post;
        },

        async updatePost(id, payload) {
            const { data } = await client.put(`/v1/posts/${id}`, payload);
            const idx = this.posts.findIndex((p) => p.id === id);
            if (idx !== -1) this.posts[idx] = data.post;
            return data.post;
        },

        async updateStatus(id, status) {
            const { data } = await client.patch(`/v1/posts/${id}/status`, { status });
            const post = data.post;
            const idx = this.posts.findIndex((p) => p.id === id);
            if (idx !== -1) this.posts[idx] = post;
            const tIdx = this.today.posts.findIndex((p) => p.id === id);
            if (tIdx !== -1) {
                this.today.posts[tIdx] = post;
                this.today.posted = this.today.posts.filter((p) => p.status === 'posted').length;
                this.today.pending = this.today.posts.filter((p) => p.status === 'scheduled').length;
            }
            return post;
        },

        async deletePost(id) {
            await client.delete(`/v1/posts/${id}`);
            this.posts = this.posts.filter((p) => p.id !== id);
            this.today.posts = this.today.posts.filter((p) => p.id !== id);
        },

        setFilter(patch) {
            this.filters = { ...this.filters, ...patch };
        },

        resetFilters() {
            this.filters = { status: '', from: '', to: '', q: '', platform: '', date: '' };
        },
    },
});
