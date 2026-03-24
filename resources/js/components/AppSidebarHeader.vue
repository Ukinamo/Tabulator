<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { SidebarTrigger } from '@/components/ui/sidebar';
import type { BreadcrumbItem } from '@/types';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

const page = usePage<{ name?: string }>();
const appName = computed(() => page.props.name ?? 'Tabulator');
</script>

<template>
    <header
        :aria-label="`${appName} toolbar`"
        class="flex h-16 shrink-0 items-center gap-2 border-b border-[#e0bec7]/25 bg-[#ffffff]/90 px-6 shadow-[0_1px_0_rgba(14,25,61,0.04)] backdrop-blur-sm transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4 dark:border-white/10 dark:bg-[#0e193d]/90"
    >
        <div class="flex min-w-0 flex-1 items-center gap-2">
            <SidebarTrigger class="-ml-1 shrink-0" />
            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
            <p
                v-else
                class="truncate text-sm font-medium text-muted-foreground"
            >
                {{ appName }}
            </p>
        </div>
    </header>
</template>
