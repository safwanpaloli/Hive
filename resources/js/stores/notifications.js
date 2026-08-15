import { defineStore } from 'pinia';
import client from '../api/client';

export const useNotificationsStore = defineStore('notifications', {
    state: () => ({
        notifications: [],
        unread: 0,
        loading: false,
    }),

    actions: {
        async fetchNotifications() {
            this.loading = true;
            try {
                const { data } = await client.get('/v1/notifications');
                this.notifications = data.notifications;
                this.unread = data.unread;
            } finally {
                this.loading = false;
            }
        },

        async markAllRead() {
            await client.post('/v1/notifications/read-all');
            this.unread = 0;
            this.notifications = this.notifications.map((n) => ({ ...n, read_at: new Date().toISOString() }));
        },
    },
});
