<script setup lang="ts">
import { useToast } from '@/composables/useToast';
import { CheckCircle2, X, XCircle, Info } from 'lucide-vue-next';

const { toasts, remove } = useToast();

const iconMap = {
    success: CheckCircle2,
    error: XCircle,
    info: Info,
    default: Info,
};

const bgClass: Record<string, string> = {
    success: 'bg-[#38F298]/95 dark:bg-[#38F298]/90 text-slate-900',
    error: 'bg-red-500/95 dark:bg-red-500/90 text-white',
    info: 'bg-[#BCD1FF]/95 dark:bg-[#BCD1FF]/90 text-slate-900',
    default: 'bg-white/95 dark:bg-slate-800/95 text-slate-900 dark:text-white border border-slate-200 dark:border-slate-700',
};
</script>

<template>
    <Teleport to="body">
        <div
            class="fixed bottom-0 left-0 right-0 z-[9999] flex flex-col items-center gap-2 p-4 pb-6 pointer-events-none"
            aria-live="polite"
        >
            <TransitionGroup
                name="toast"
                tag="div"
                class="flex w-full max-w-md flex-col gap-2"
            >
                <div
                    v-for="t in toasts"
                    :key="t.id"
                    class="pointer-events-auto flex min-w-0 items-center gap-3 rounded-2xl px-4 py-3 shadow-lg ring-1 ring-black/5 dark:ring-white/10"
                    :class="bgClass[t.type]"
                >
                    <component
                        :is="iconMap[t.type]"
                        class="h-5 w-5 shrink-0"
                    />
                    <p class="min-w-0 flex-1 text-sm font-medium">
                        {{ t.message }}
                    </p>
                    <button
                        type="button"
                        class="shrink-0 rounded-full p-1 opacity-70 transition hover:opacity-100 focus:outline-none focus:ring-2"
                        aria-label="Dismiss"
                        @click="remove(t.id)"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>
            </TransitionGroup>
        </div>
    </Teleport>
</template>

<style scoped>
.toast-enter-active,
.toast-leave-active {
    transition: all 0.25s ease;
}
.toast-enter-from {
    opacity: 0;
    transform: translateY(100%);
}
.toast-leave-to {
    opacity: 0;
    transform: translateY(-20%);
}
.toast-move {
    transition: transform 0.25s ease;
}
</style>
