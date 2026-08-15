export function formatDate(value, opts = {}) {
    if (!value) return '—';
    return new Intl.DateTimeFormat(undefined, {
        weekday: 'short',
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        ...opts,
    }).format(new Date(value));
}

export function formatTime(value) {
    if (!value) return '—';
    return new Intl.DateTimeFormat(undefined, {
        hour: 'numeric',
        minute: '2-digit',
    }).format(new Date(value));
}

export function formatDateTime(value) {
    if (!value) return '—';
    return `${formatDate(value)} · ${formatTime(value)}`;
}

export function isToday(value) {
    if (!value) return false;
    const d = new Date(value);
    const now = new Date();
    return (
        d.getFullYear() === now.getFullYear() &&
        d.getMonth() === now.getMonth() &&
        d.getDate() === now.getDate()
    );
}

export function relativeTime(value) {
    if (!value) return '—';
    const then = new Date(value);
    const diffMs = then - Date.now();
    const abs = Math.abs(diffMs);
    const mins = Math.round(abs / 60000);

    if (mins < 1) return 'just now';
    if (mins < 60) return `${mins}m ${diffMs < 0 ? 'ago' : 'from now'}`;

    const hours = Math.round(mins / 60);
    if (hours < 24) return `${hours}h ${diffMs < 0 ? 'ago' : 'from now'}`;

    const days = Math.round(hours / 24);
    return `${days}d ${diffMs < 0 ? 'ago' : 'from now'}`;
}

export function toLocalDatetime(value) {
    if (!value) return '';
    const d = new Date(value);
    const pad = (n) => String(n).padStart(2, '0');
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
}

export function humanizePlatform(name) {
    const icons = {
        Facebook: '📘',
        Instagram: '📸',
        'X (Twitter)': '𝕏',
        LinkedIn: '💼',
        YouTube: '▶️',
        TikTok: '🎵',
        Pinterest: '📌',
        Threads: '🧵',
    };
    return { icon: icons[name] || '🌐', label: name };
}
