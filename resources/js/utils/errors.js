import { useToast } from '../composables/useToast';

export function extractApiError(error, fallback = 'Something went wrong.') {
    const data = error?.response?.data;
    if (data?.message) return data.message;

    if (data?.errors) {
        const first = Object.values(data.errors)[0];
        if (Array.isArray(first) && first.length) return first[0];
    }

    return fallback;
}

export { useToast };
