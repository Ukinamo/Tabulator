import { ref } from 'vue';

export type ToastType = 'success' | 'error' | 'info' | 'default';

export type Toast = {
    id: number;
    message: string;
    type: ToastType;
    duration: number;
    createdAt: number;
};

const toasts = ref<Toast[]>([]);
let nextId = 1;
const DEFAULT_DURATION = 3500;

function add(message: string, type: ToastType = 'default', duration = DEFAULT_DURATION): void {
    const id = nextId++;
    const toast: Toast = { id, message, type, duration, createdAt: Date.now() };
    toasts.value = [...toasts.value, toast];

    if (duration > 0) {
        setTimeout(() => {
            remove(id);
        }, duration);
    }
}

function remove(id: number): void {
    toasts.value = toasts.value.filter((t) => t.id !== id);
}

function success(message: string, duration = DEFAULT_DURATION): void {
    add(message, 'success', duration);
}

function error(message: string, duration = DEFAULT_DURATION): void {
    add(message, 'error', duration);
}

function info(message: string, duration = DEFAULT_DURATION): void {
    add(message, 'info', duration);
}

export function useToast() {
    return {
        toasts,
        success,
        error,
        info,
        add,
        remove,
    };
}
