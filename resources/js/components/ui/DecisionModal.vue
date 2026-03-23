<script setup lang="ts">
import { Dialog, DialogContent, DialogClose } from '@/components/ui/dialog';

type Props = {
    open: boolean;
    title: string;
    message: string;
    confirmLabel?: string;
    cancelLabel?: string;
    variant?: 'primary' | 'danger' | 'default';
    loading?: boolean;
};

withDefaults(defineProps<Props>(), {
    confirmLabel: 'Confirm',
    cancelLabel: 'Cancel',
    variant: 'default',
    loading: false,
});

const emit = defineEmits<{
    confirm: [];
    cancel: [];
}>();

function onConfirm() {
    emit('confirm');
}

function onCancel() {
    emit('cancel');
}
</script>

<template>
    <Dialog :open="open" @update:open="(v: boolean) => !v && onCancel()">
        <DialogContent
            :show-close-button="false"
            class="max-w-[340px] rounded-3xl border border-[#e8e6f5] p-0 shadow-2xl dark:border-[#2a3558] dark:bg-[#0e193d]"
            @pointer-down-outside="onCancel"
            @escape-key-down="onCancel"
        >
            <div class="p-6 text-center">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white">
                    {{ title }}
                </h3>
                <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                    {{ message }}
                </p>
            </div>
            <div class="flex flex-col border-t border-slate-200 dark:border-slate-700">
                <button
                    type="button"
                    class="min-h-[48px] border-b border-slate-200 text-sm font-semibold transition active:opacity-80 dark:border-slate-700"
                    :class="
                        variant === 'danger'
                            ? 'text-red-500 dark:text-red-400'
                            : 'text-[#b40066] dark:text-[#da2180]'
                    "
                    :disabled="loading"
                    @click="onConfirm"
                >
                    <span v-if="loading" class="inline-flex items-center gap-2">
                        <span class="inline-block h-4 w-4 animate-spin rounded-full border-2 border-current border-t-transparent" />
                        {{ confirmLabel }}
                    </span>
                    <span v-else>{{ confirmLabel }}</span>
                </button>
                <DialogClose>
                    <button
                        type="button"
                        class="min-h-[48px] w-full text-sm font-semibold text-slate-600 dark:text-slate-300"
                        :disabled="loading"
                    >
                        {{ cancelLabel }}
                    </button>
                </DialogClose>
            </div>
        </DialogContent>
    </Dialog>
</template>
