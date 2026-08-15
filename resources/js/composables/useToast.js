import { reactive, readonly } from 'vue';

const state = reactive({ items: [] });

let counter = 0;

function push(type, message) {
    const id = ++counter;
    state.items.push({ id, type, message });
    setTimeout(() => {
        const idx = state.items.findIndex((t) => t.id === id);
        if (idx !== -1) state.items.splice(idx, 1);
    }, 4200);
}

export function useToast() {
    return {
        toasts: readonly(state.items),
        success: (message) => push('success', message),
        error: (message) => push('error', message),
        info: (message) => push('info', message),
    };
}
