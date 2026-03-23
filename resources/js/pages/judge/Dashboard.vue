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
                class="neon-card border border-[#e8e6f5] px-5 py-4"
            >
                <h1 class="font-headline text-lg font-semibold text-[#0e193d]">
                    Welcome, {{ user.name }}
                </h1>
                <p
                    v-if="event"
                    class="mt-1 text-sm text-[#594048]"
                >
                    You are scoring: <span class="font-medium text-[#0e193d]">{{ event.name }}</span>
                    · {{ event.venue || 'Venue TBA' }} ·
                    {{ event.event_date }}
                </p>
            </section>

            <section
                v-if="statusSummary"
                :class="[
                    'rounded-2xl border px-4 py-3 text-sm',
                    statusSummary.type === 'submitted'
                        ? 'border-[#006a3d]/35 bg-[#006a3d]/10 text-[#006a3d]'
                        : 'border-amber-400/50 bg-amber-500/10 text-amber-900',
                ]"
            >
                {{ statusSummary.message }}
            </section>

            <section
                class="neon-card border border-[#e8e6f5] p-4"
            >
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-[#0e193d]">
                        My scores by category
                    </h2>
                    <Link
                        href="/judge/scoresheet"
                        class="neon-btn-primary inline-flex items-center px-4 py-1.5 text-xs no-underline"
                    >
                        Go to scoresheet
                    </Link>
                </div>
                <p class="mb-3 text-xs text-[#594048]">
                    The scoresheet lists all contestants for this event. New contestants added by the admin appear there so you can score them.
                </p>

                <div
                    v-if="!categorySummary || Object.keys(categorySummary).length === 0"
                    class="py-4 text-sm text-[#594048]"
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
                        class="rounded-xl border border-[#ebedff] bg-[#f3f2ff]/80 p-3"
                    >
                        <p class="text-xs font-medium text-[#4a5e86]">
                            {{ name }}
                        </p>
                        <p class="mt-2 text-2xl font-semibold text-[#0e193d]">
                            {{ (row as any).average.toFixed(2) }}
                        </p>
                        <p class="mt-1 text-xs text-[#594048]">
                            {{ (row as any).count }} score(s) given
                        </p>
                    </div>
                </div>
            </section>
        </div>
    </AppLayout>
</template>

