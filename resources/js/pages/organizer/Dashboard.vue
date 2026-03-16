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
        <div class="flex flex-col gap-6 p-4">
            <section
                v-if="event"
                class="rounded-2xl border border-slate-700 bg-slate-900/80 px-5 py-4"
            >
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 class="text-lg font-semibold text-white">
                            {{ event.name }}
                        </h1>
                        <p class="text-sm text-slate-300">
                            {{ event.venue || 'Venue TBA' }} ·
                            {{ event.event_date }}
                        </p>
                    </div>
                    <span
                        class="inline-flex items-center rounded-full bg-[#BCD1FF]/20 px-3 py-1 text-xs font-medium text-[#BCD1FF]"
                    >
                        Status: {{ event.status }}
                    </span>
                </div>
            </section>

            <section
                class="grid gap-4 md:grid-cols-2"
            >
                <div
                    class="rounded-2xl border border-slate-700 bg-slate-900/80 p-4"
                >
                    <h2 class="mb-3 text-sm font-semibold text-white">
                        Category Weights
                    </h2>

                    <div
                        v-if="!weights"
                        class="py-4 text-sm text-slate-400"
                    >
                        No categories configured yet.
                    </div>

                    <div v-else class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-slate-300">
                                Total weight
                            </span>
                            <span
                                class="text-xs font-semibold"
                                :class="
                                    Math.round(weights.total) === 100
                                        ? 'text-[#38F298]'
                                        : 'text-amber-400'
                                "
                            >
                                {{ weights.total.toFixed(2) }}% / 100%
                            </span>
                        </div>
                        <div class="h-2 w-full overflow-hidden rounded-full bg-slate-800">
                            <div
                                class="h-full bg-[#38F298]"
                                :style="{ width: Math.min(weights.total, 100) + '%' }"
                            />
                        </div>

                        <ul class="space-y-2 text-sm text-slate-200">
                            <li
                                v-for="cat in weights.categories"
                                :key="cat.id"
                                class="flex items-center justify-between"
                            >
                                <span>{{ cat.name }}</span>
                                <span class="text-slate-300">
                                    {{ cat.weight.toFixed(2) }}%
                                </span>
                            </li>
                        </ul>

                        <p
                            v-if="Math.round(weights.total) !== 100"
                            class="mt-2 text-xs text-amber-300"
                        >
                            Category weights must total 100% before the event can
                            start.
                        </p>
                    </div>
                </div>

                <div
                    class="rounded-2xl border border-slate-700 bg-slate-900/80 p-4"
                >
                    <h2 class="mb-3 text-sm font-semibold text-white">
                        Judge Progress
                    </h2>

                    <div
                        v-if="judgeProgress.length === 0"
                        class="py-4 text-sm text-slate-400"
                    >
                        No judges configured yet.
                    </div>

                    <ul
                        v-else
                        class="space-y-2 text-sm text-slate-100"
                    >
                        <li
                            v-for="row in judgeProgress"
                            :key="row.judge_id"
                            class="flex items-center justify-between"
                        >
                            <div>
                                <p>{{ row.judge_name }}</p>
                                <p class="text-xs text-slate-400">
                                    {{ row.submitted_count }}/{{ row.total_count }}
                                    scores submitted
                                </p>
                            </div>
                            <span
                                class="inline-flex rounded-full px-3 py-1 text-xs font-medium"
                                :class="{
                                    'bg-slate-700 text-slate-100':
                                        row.status === 'not_started',
                                    'bg-amber-500/20 text-amber-300':
                                        row.status === 'in_progress',
                                    'bg-[#38F298]/20 text-[#38F298]':
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

