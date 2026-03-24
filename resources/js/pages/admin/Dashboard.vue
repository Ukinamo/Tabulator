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
            <div class="sticky top-0 z-10 flex items-center justify-between border-b border-border bg-card/90 px-6 py-4 shadow-[0_1px_0_rgba(14,25,61,0.04)] backdrop-blur-sm">
                <div>
                    <h1 class="font-headline text-2xl font-bold tracking-tight text-foreground">
                        Dashboard
                    </h1>
                    <p v-if="event" class="mt-0.5 text-sm text-muted-foreground">
                        {{ event.name }}
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        class="relative rounded-full p-2 text-muted-foreground hover:bg-muted"
                        aria-label="Notifications"
                    >
                        <Bell class="h-5 w-5" />
                        <span class="absolute right-1 top-1 h-2 w-2 rounded-full bg-[#b40066]" />
                    </button>
                    <span
                        v-if="event"
                        class="rounded-full bg-gradient-to-r from-[#b40066] to-[#da2180] px-4 py-1.5 text-xs font-semibold uppercase tracking-wide text-white shadow-[0_0_12px_rgba(180,0,102,0.3)]"
                    >
                        {{ event.status }}
                    </span>
                </div>
            </div>

            <div class="flex flex-col gap-6 p-6">
                <!-- Stat cards with colored left borders -->
                <section v-if="stats" class="grid gap-4 md:grid-cols-4">
                    <div
                        class="stat-border-primary neon-card rounded-2xl p-5 shadow-[0_4px_24px_rgba(14,25,61,0.06)]"
                    >
                        <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                            Contestants
                        </p>
                        <p class="mt-2 text-3xl font-bold tabular-nums text-foreground">
                            {{ stats.total_contestants }}
                        </p>
                    </div>
                    <div
                        class="stat-border-secondary neon-card rounded-2xl p-5 shadow-[0_4px_24px_rgba(14,25,61,0.06)]"
                    >
                        <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                            Judges
                        </p>
                        <p class="mt-2 text-3xl font-bold tabular-nums text-foreground">
                            {{ stats.total_judges }}
                        </p>
                    </div>
                    <div
                        class="stat-border-accent neon-card rounded-2xl p-5 shadow-[0_4px_24px_rgba(14,25,61,0.06)]"
                    >
                        <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                            Categories
                        </p>
                        <p class="mt-2 text-3xl font-bold tabular-nums text-foreground">
                            {{ stats.total_categories }}
                        </p>
                    </div>
                    <div
                        class="stat-border-warning neon-card rounded-2xl p-5 shadow-[0_4px_24px_rgba(14,25,61,0.06)]"
                    >
                        <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                            Scores Submitted
                        </p>
                        <p class="mt-2 text-3xl font-bold tabular-nums text-foreground">
                            {{ stats.submitted_scores }}
                            <span class="text-lg font-medium text-muted-foreground">
                                / {{ stats.required_scores }}
                            </span>
                        </p>
                    </div>
                </section>

                <!-- Judge progress with progress bars -->
                <section class="neon-card rounded-2xl p-5 shadow-[0_4px_24px_rgba(14,25,61,0.06)]">
                    <div class="mb-4 flex items-center justify-between">
                        <h2 class="font-headline text-lg font-semibold text-foreground">
                            Judge progress
                        </h2>
                        <Link
                            href="/admin/scores"
                            class="rounded-full bg-gradient-to-r from-[#b40066] to-[#da2180] px-4 py-2 text-sm font-semibold text-white shadow-[0_0_12px_rgba(180,0,102,0.35)] transition hover:opacity-95"
                        >
                            Review scores
                        </Link>
                    </div>

                    <div
                        v-if="judgeProgress.length === 0"
                        class="py-8 text-center text-sm text-muted-foreground"
                    >
                        No judges configured yet.
                    </div>

                    <div v-else class="space-y-4">
                        <div
                            v-for="row in judgeProgress"
                            :key="row.judge_id"
                            class="flex flex-wrap items-center gap-3 rounded-xl bg-muted p-4"
                        >
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-secondary text-sm font-semibold text-secondary-foreground">
                                {{ row.judge_name.split(' ').map((n) => n[0]).join('').slice(0, 2) }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="font-medium text-foreground">{{ row.judge_name }}</p>
                                <div class="mt-2 flex items-center gap-3">
                                    <div class="h-2 flex-1 overflow-hidden rounded-full bg-input">
                                        <div
                                            class="h-full rounded-full bg-[#006a3d] transition-all"
                                            :style="{
                                                width: row.total_count
                                                    ? `${(row.submitted_count / row.total_count) * 100}%`
                                                    : '0%',
                                            }"
                                        />
                                    </div>
                                    <span class="text-sm text-muted-foreground">
                                        {{ row.submitted_count }}/{{ row.total_count }}
                                    </span>
                                    <span
                                        v-if="row.status === 'submitted'"
                                        class="rounded-full bg-[#006a3d]/12 px-2.5 py-1 text-xs font-medium text-[#006a3d]"
                                    >
                                        Submitted
                                    </span>
                                    <span
                                        v-else-if="row.status === 'in_progress'"
                                        class="rounded-full bg-[#ffd166]/30 px-2.5 py-1 text-xs font-medium text-[#7a5f00]"
                                    >
                                        In progress
                                    </span>
                                    <span
                                        v-else
                                        class="rounded-full bg-input px-2.5 py-1 text-xs font-medium text-muted-foreground"
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
                        class="inline-flex items-center rounded-full bg-secondary px-5 py-2.5 text-sm font-medium text-secondary-foreground transition hover:opacity-90"
                    >
                        View score grid
                    </Link>
                    <Link
                        v-if="canPublish"
                        href="/admin/results"
                        class="inline-flex items-center rounded-full bg-gradient-to-r from-[#b40066] to-[#da2180] px-5 py-2.5 text-sm font-semibold text-white shadow-[0_0_18px_rgba(180,0,102,0.35)] transition hover:opacity-95"
                    >
                        Publish results
                    </Link>
                    <span
                        v-else
                        class="inline-flex cursor-not-allowed items-center rounded-full bg-muted px-5 py-2.5 text-sm font-semibold text-muted-foreground"
                    >
                        Publish results
                    </span>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
