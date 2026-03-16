<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

type Props = {
    user: {
        name: string;
    };
    event: {
        id: number;
        name: string;
        event_date: string;
        venue: string | null;
    } | null;
    statusSummary:
        | {
              type: 'pending' | 'submitted';
              message: string;
          }
        | null;
    categorySummary: Record<
        string,
        {
            average: number;
            count: number;
        }
    > | null;
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Judge Dashboard',
        href: '/judge/dashboard',
    },
];
</script>

<template>
    <Head title="Judge Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4">
            <section
                class="rounded-2xl border border-slate-700 bg-slate-900/80 px-5 py-4"
            >
                <h1 class="text-lg font-semibold text-white">
                    Welcome, {{ user.name }}
                </h1>
                <p
                    v-if="event"
                    class="mt-1 text-sm text-slate-300"
                >
                    You are scoring: <span class="font-medium">{{ event.name }}</span>
                    · {{ event.venue || 'Venue TBA' }} ·
                    {{ event.event_date }}
                </p>
            </section>

            <section
                v-if="statusSummary"
                :class="[
                    'rounded-2xl border px-4 py-3 text-sm',
                    statusSummary.type === 'submitted'
                        ? 'border-[#38F298]/50 bg-[#38F298]/10 text-[#38F298]'
                        : 'border-amber-400/60 bg-amber-500/10 text-amber-200',
                ]"
            >
                {{ statusSummary.message }}
            </section>

            <section
                class="rounded-2xl border border-slate-700 bg-slate-900/80 p-4"
            >
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-white">
                        My scores by category
                    </h2>
                    <Link
                        href="/judge/scoresheet"
                        class="inline-flex items-center rounded-full bg-[#F23892] px-4 py-1.5 text-xs font-medium text-white shadow-[0_0_18px_rgba(242,56,146,0.6)] hover:bg-[#d0206e]"
                    >
                        Go to scoresheet
                    </Link>
                </div>
                <p class="mb-3 text-xs text-slate-400">
                    The scoresheet lists all contestants for this event. New contestants added by the admin appear there so you can score them.
                </p>

                <div
                    v-if="!categorySummary || Object.keys(categorySummary).length === 0"
                    class="py-4 text-sm text-slate-400"
                >
                    No scores yet. Start scoring contestants from the scoresheet.
                </div>

                <div
                    v-else
                    class="grid gap-3 md:grid-cols-3"
                >
                    <div
                        v-for="(row, name) in categorySummary"
                        :key="name as string"
                        class="rounded-xl border border-slate-700 bg-slate-950/70 p-3"
                    >
                        <p class="text-xs font-medium text-slate-300">
                            {{ name }}
                        </p>
                        <p class="mt-2 text-2xl font-semibold text-white">
                            {{ (row as any).average.toFixed(2) }}
                        </p>
                        <p class="mt-1 text-xs text-slate-400">
                            {{ (row as any).count }} score(s) given
                        </p>
                    </div>
                </div>
            </section>
        </div>
    </AppLayout>
</template>

