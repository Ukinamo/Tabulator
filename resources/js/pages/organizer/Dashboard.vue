<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

type CategoryWeight = {
    id: number;
    name: string;
    weight: number;
};

type Props = {
    event: {
        id: number;
        name: string;
        event_date: string;
        venue: string | null;
        status: string;
    } | null;
    weights: {
        total: number;
        categories: CategoryWeight[];
    } | null;
    judgeProgress: {
        judge_id: number;
        judge_name: string;
        submitted_count: number;
        total_count: number;
        status: string;
    }[];
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Organizer Dashboard',
        href: '/organizer/dashboard',
    },
];
</script>

<template>
    <Head title="Organizer Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="mx-auto flex w-full max-w-6xl flex-col gap-6 p-4 md:p-6"
        >
            <section
                v-if="event"
                class="neon-card border border-[#e8e6f5] px-5 py-4"
            >
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 class="font-headline text-lg font-semibold text-[#0e193d]">
                            {{ event.name }}
                        </h1>
                        <p class="text-sm text-[#594048]">
                            {{ event.venue || 'Venue TBA' }} ·
                            {{ event.event_date }}
                        </p>
                    </div>
                    <span
                        class="inline-flex items-center rounded-full bg-[#4a5e86]/12 px-3 py-1 text-xs font-medium text-[#4a5e86]"
                    >
                        Status: {{ event.status }}
                    </span>
                </div>
            </section>

            <section
                class="grid gap-4 md:grid-cols-2"
            >
                <div
                    class="neon-card border border-[#e8e6f5] p-4"
                >
                    <h2 class="mb-3 text-sm font-semibold text-[#0e193d]">
                        Category Weights
                    </h2>

                    <div
                        v-if="!weights"
                        class="py-4 text-sm text-[#594048]"
                    >
                        No categories configured yet.
                    </div>

                    <div v-else class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-[#594048]">
                                Total weight
                            </span>
                            <span
                                class="text-xs font-semibold"
                                :class="
                                    Math.round(weights.total) === 100
                                        ? 'text-[#006a3d]'
                                        : 'text-amber-600'
                                "
                            >
                                {{ weights.total.toFixed(2) }}% / 100%
                            </span>
                        </div>
                        <div class="h-2 w-full overflow-hidden rounded-full bg-[#ebedff]">
                            <div
                                class="h-full rounded-full bg-[#006a3d]"
                                :style="{ width: Math.min(weights.total, 100) + '%' }"
                            />
                        </div>

                        <ul class="space-y-2 text-sm text-[#0e193d]">
                            <li
                                v-for="cat in weights.categories"
                                :key="cat.id"
                                class="flex items-center justify-between rounded-xl bg-[#ebedff]/80 px-3 py-1.5"
                            >
                                <span>{{ cat.name }}</span>
                                <span class="text-[#594048]">
                                    {{ cat.weight.toFixed(2) }}%
                                </span>
                            </li>
                        </ul>

                        <p
                            v-if="Math.round(weights.total) !== 100"
                            class="mt-2 text-xs text-amber-700"
                        >
                            Category weights must total 100% before the event can
                            start.
                        </p>
                    </div>
                </div>

                <div
                    class="neon-card border border-[#e8e6f5] p-4"
                >
                    <h2 class="mb-3 text-sm font-semibold text-[#0e193d]">
                        Judge Progress
                    </h2>

                    <div
                        v-if="judgeProgress.length === 0"
                        class="py-4 text-sm text-[#594048]"
                    >
                        No judges configured yet.
                    </div>

                    <ul
                        v-else
                        class="space-y-2 text-sm text-[#0e193d]"
                    >
                        <li
                            v-for="row in judgeProgress"
                            :key="row.judge_id"
                            class="flex items-center justify-between rounded-xl bg-[#ebedff]/80 px-3 py-2"
                        >
                            <div>
                                <p class="font-medium">{{ row.judge_name }}</p>
                                <p class="text-xs text-[#594048]">
                                    {{ row.submitted_count }}/{{ row.total_count }}
                                    scores submitted
                                </p>
                            </div>
                            <span
                                class="inline-flex rounded-full px-3 py-1 text-xs font-medium"
                                :class="{
                                    'bg-[#4a5e86]/15 text-[#4a5e86]':
                                        row.status === 'not_started',
                                    'bg-amber-500/15 text-amber-800':
                                        row.status === 'in_progress',
                                    'bg-[#006a3d]/15 text-[#006a3d]':
                                        row.status === 'submitted',
                                }"
                            >
                                {{
                                    row.status === 'not_started'
                                        ? 'Not Started'
                                        : row.status === 'in_progress'
                                          ? 'In Progress'
                                          : 'Submitted'
                                }}
                            </span>
                        </li>
                    </ul>
                </div>
            </section>
        </div>
    </AppLayout>
</template>

