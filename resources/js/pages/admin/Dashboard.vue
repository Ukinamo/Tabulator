<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Bell } from 'lucide-vue-next';
import type { BreadcrumbItem } from '@/types';

type JudgeProgress = {
    judge_id: number;
    judge_name: string;
    submitted_count: number;
    total_count: number;
    status: string;
};

type Props = {
    event: {
        id: number;
        name: string;
        event_date: string;
        venue: string | null;
        status: string;
    } | null;
    stats: {
        total_contestants: number;
        total_judges: number;
        total_categories: number;
        submitted_scores: number;
        required_scores: number;
    } | null;
    judgeProgress: JudgeProgress[];
    canPublish: boolean;
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin Dashboard', href: '/admin/dashboard' },
];
</script>

<template>
    <Head title="Admin Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col">
            <!-- Sticky white topbar -->
            <div class="sticky top-0 z-10 flex items-center justify-between border-b border-slate-200 bg-white px-6 py-4 dark:border-slate-700 dark:bg-slate-800">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                        Dashboard
                    </h1>
                    <p v-if="event" class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">
                        {{ event.name }}
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        class="relative rounded-full p-2 text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-700"
                        aria-label="Notifications"
                    >
                        <Bell class="h-5 w-5" />
                        <span class="absolute right-1 top-1 h-2 w-2 rounded-full bg-[#F23892]" />
                    </button>
                    <span
                        v-if="event"
                        class="rounded-full bg-[#F23892] px-4 py-1.5 text-xs font-semibold uppercase tracking-wide text-white"
                    >
                        {{ event.status }}
                    </span>
                </div>
            </div>

            <div class="flex flex-col gap-6 p-6">
                <!-- Stat cards with colored left borders -->
                <section v-if="stats" class="grid gap-4 md:grid-cols-4">
                    <div
                        class="stat-border-primary rounded-2xl border border-slate-700 bg-slate-800/80 p-5"
                    >
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                            Contestants
                        </p>
                        <p class="mt-2 text-3xl font-bold text-white">
                            {{ stats.total_contestants }}
                        </p>
                    </div>
                    <div
                        class="stat-border-secondary rounded-2xl border border-slate-700 bg-slate-800/80 p-5"
                    >
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                            Judges
                        </p>
                        <p class="mt-2 text-3xl font-bold text-white">
                            {{ stats.total_judges }}
                        </p>
                    </div>
                    <div
                        class="stat-border-accent rounded-2xl border border-slate-700 bg-slate-800/80 p-5"
                    >
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                            Categories
                        </p>
                        <p class="mt-2 text-3xl font-bold text-white">
                            {{ stats.total_categories }}
                        </p>
                    </div>
                    <div
                        class="stat-border-warning rounded-2xl border border-slate-700 bg-slate-800/80 p-5"
                    >
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                            Scores Submitted
                        </p>
                        <p class="mt-2 text-3xl font-bold text-white">
                            {{ stats.submitted_scores }}
                            <span class="text-lg font-medium text-slate-400">
                                / {{ stats.required_scores }}
                            </span>
                        </p>
                    </div>
                </section>

                <!-- Judge progress with progress bars -->
                <section class="rounded-2xl border border-slate-700 bg-slate-800/80 p-5">
                    <div class="mb-4 flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-white">
                            Judge progress
                        </h2>
                        <Link
                            href="/admin/scores"
                            class="rounded-full bg-[#F23892] px-4 py-2 text-sm font-semibold text-white shadow-[0_0_12px_rgba(242,56,146,0.4)] transition hover:bg-[#d0206e]"
                        >
                            Review scores
                        </Link>
                    </div>

                    <div
                        v-if="judgeProgress.length === 0"
                        class="py-8 text-center text-sm text-slate-400"
                    >
                        No judges configured yet.
                    </div>

                    <div v-else class="space-y-4">
                        <div
                            v-for="row in judgeProgress"
                            :key="row.judge_id"
                            class="flex flex-wrap items-center gap-3 rounded-xl bg-slate-900/50 p-4"
                        >
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#BCD1FF]/30 text-sm font-semibold text-[#BCD1FF]">
                                {{ row.judge_name.split(' ').map((n) => n[0]).join('').slice(0, 2) }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="font-medium text-white">{{ row.judge_name }}</p>
                                <div class="mt-2 flex items-center gap-3">
                                    <div class="h-2 flex-1 overflow-hidden rounded-full bg-slate-700">
                                        <div
                                            class="h-full rounded-full bg-[#38F298] transition-all"
                                            :style="{
                                                width: row.total_count
                                                    ? `${(row.submitted_count / row.total_count) * 100}%`
                                                    : '0%',
                                            }"
                                        />
                                    </div>
                                    <span class="text-sm text-slate-300">
                                        {{ row.submitted_count }}/{{ row.total_count }}
                                    </span>
                                    <span
                                        v-if="row.status === 'submitted'"
                                        class="rounded-full bg-[#38F298]/20 px-2.5 py-1 text-xs font-medium text-[#38F298]"
                                    >
                                        Submitted
                                    </span>
                                    <span
                                        v-else-if="row.status === 'in_progress'"
                                        class="rounded-full bg-[#FFD166]/20 px-2.5 py-1 text-xs font-medium text-[#FFD166]"
                                    >
                                        In progress
                                    </span>
                                    <span
                                        v-else
                                        class="rounded-full bg-slate-600/50 px-2.5 py-1 text-xs font-medium text-slate-400"
                                    >
                                        Not started
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Quick actions -->
                <div class="flex flex-wrap gap-3">
                    <Link
                        href="/admin/scores"
                        class="inline-flex items-center rounded-xl border border-slate-600 bg-slate-800 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-slate-700"
                    >
                        View score grid
                    </Link>
                    <Link
                        v-if="canPublish"
                        href="/admin/results"
                        class="inline-flex items-center rounded-xl bg-[#F23892] px-5 py-2.5 text-sm font-semibold text-white shadow-[0_0_18px_rgba(242,56,146,0.5)] transition hover:bg-[#d0206e]"
                    >
                        Publish results
                    </Link>
                    <span
                        v-else
                        class="inline-flex cursor-not-allowed items-center rounded-xl bg-pink-500/50 px-5 py-2.5 text-sm font-semibold text-white"
                    >
                        Publish results
                    </span>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
