import { useAuthStore } from '../stores/auth';
import { usePostsStore } from '../stores/posts';

export function useBrowserNotifications() {
    const supported = () => typeof window !== 'undefined' && 'Notification' in window;

    function permission() {
        return supported() ? Notification.permission : 'unsupported';
    }

    async function requestPermission() {
        if (!supported()) return 'unsupported';
        return Notification.requestPermission();
    }

    function notify(title, options = {}) {
        if (!supported() || Notification.permission !== 'granted') return;
        try {
            const notification = new Notification(title, {
                icon: '/favicon.svg',
                ...options,
            });
            if (options.url) {
                notification.onclick = () => {
                    window.focus();
                    window.location.href = options.url;
                };
            }
        } catch {
            // Notifications unavailable in this context.
        }
    }

    function remindAboutToday() {
        const auth = useAuthStore();
        const posts = usePostsStore();

        if (!auth.isAuthenticated) return;
        if (!supported() || Notification.permission !== 'granted') return;

        const pending = posts.today.posts.filter((p) => p.status === 'scheduled');
        const posted = posts.today.posts.filter((p) => p.status === 'posted').length;

        if (pending.length === 0) {
            if (posted > 0) {
                notify(`Nice work! ${posted} post(s) marked as posted today.`, {
                    body: 'Keep the streak going.',
                });
            }
            return;
        }

        const titles = pending.map((p) => `• ${p.title}`).join('\n');

        notify(`📬 ${pending.length} post(s) scheduled today`, {
            body: titles,
            tag: 'daily-queue',
            url: '/',
        });
    }

    return { supported, permission, requestPermission, notify, remindAboutToday };
}
