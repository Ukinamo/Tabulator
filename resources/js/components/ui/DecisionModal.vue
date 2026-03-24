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
            class="max-w-[340px] rounded-3xl border border-border bg-card p-0 text-card-foreground shadow-2xl"
            @pointer-down-outside="onCancel"
            @escape-key-down="onCancel"
        >
            <div class="p-6 text-center">
                <h3 class="text-lg font-semibold text-foreground">
                    {{ title }}
                </h3>
                <p class="mt-2 text-sm text-muted-foreground">
                    {{ message }}
                </p>
            </div>
            <div class="flex flex-col border-t border-border">
                <button
                    type="button"
                    class="min-h-[48px] border-b border-border text-sm font-semibold transition active:opacity-80"
                    :class="
                        variant === 'danger'
                            ? 'text-red-500'
                            : 'text-[#b40066]'
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
                        class="min-h-[48px] w-full text-sm font-semibold text-muted-foreground"
                        :disabled="loading"
                    >
                        {{ cancelLabel }}
                    </button>
                </DialogClose>
            </div>
        </DialogContent>
    </Dialog>
</template>
