<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';

withDefaults(
    defineProps<{
        subtitle?: string;
    }>(),
    { subtitle: '' },
);
</script>

<template>
    <div
        class="flex aspect-square size-8 shrink-0 items-center justify-center rounded-full bg-[#da2180] text-white shadow-[0_0_12px_rgba(180,0,102,0.35)]"
    >
        <AppLogoIcon class="size-5 fill-current text-white" />
    </div>
    <div class="ml-2 grid min-w-0 flex-1 text-left text-sm leading-tight">
        <span class="truncate font-headline font-bold tracking-tight text-white">Tabulator</span>
        <span v-if="subtitle" class="truncate text-[10px] font-medium text-slate-400">{{ subtitle }}</span>
    </div>
</template>
