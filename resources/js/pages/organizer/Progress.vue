<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { onMounted, onUnmounted, ref, watch } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

type ProgressRow = {
    judge_id: number;
    judge_name: string;
    submitted_count: number;
    total_count: number;
    status: string;
};

type Props = { event: { id: number; name: string } | null };

const props = defineProps<Props>();

const progress = ref<ProgressRow[]>([]);
const loading = ref(true);
let interval: ReturnType<typeof setInterval> | null = null;

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Organizer', href: '/organizer/dashboard' },
    { title: 'Progress', href: '/organizer/progress' },
];

async function fetchProgress() {
    if (!props.event) return;
    loading.value = true;
    try {
        const r = await fetch(`/api/v1/organizer/events/${props.event.id}/progress`, { credentials: 'include', headers: { Accept: 'application/json' } });
        if (!r.ok) return;
        const json = await r.json();
        progress.value = (json.data ?? []) as ProgressRow[];
    } finally {
        loading.value = false;
    }
}

watch(() => props.event?.id, (id) => { if (id) fetchProgress(); }, { immediate: true });
onMounted(() => {
    if (props.event) fetchProgress();
    interval = setInterval(() => { if (props.event) fetchProgress(); }, 30000);
});
onUnmounted(() => { if (interval) clearInterval(interval); });
</script>

<template>
    <Head title="Progress - Organizer" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4">
            <h1 class="font-headline text-xl font-semibold text-[#0e193d]">Judging Progress</h1>
            <p v-if="!event" class="text-[#594048]">No event selected.</p>
            <div v-else-if="loading" class="neon-card border border-[#e8e6f5] p-8 text-center text-[#594048]">Loading…</div>
            <div v-else class="neon-card overflow-hidden border border-[#e8e6f5]">
                <table class="min-w-full text-left text-sm text-[#0e193d]">
                    <thead class="border-b border-[#e8e6f5] bg-[#f3f2ff] text-xs uppercase text-[#594048]">
                        <tr>
                            <th class="px-4 py-3">Judge</th>
                            <th class="px-4 py-3">Submitted</th>
                            <th class="px-4 py-3">% Complete</th>
                            <th class="px-4 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in progress" :key="row.judge_id" class="border-b border-[#ebedff] transition hover:bg-[#ebedff]/50">
                            <td class="px-4 py-3">{{ row.judge_name }}</td>
                            <td class="px-4 py-3">{{ row.submitted_count }}/{{ row.total_count }}</td>
                            <td class="px-4 py-3">
                                {{ row.total_count ? Math.round((row.submitted_count / row.total_count) * 100) : 0 }}%
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    class="rounded-full px-2 py-0.5 text-xs font-medium"
                                    :class="{
                                        'bg-[#4a5e86]/15 text-[#4a5e86]': row.status === 'not_started',
                                        'bg-amber-500/15 text-amber-800': row.status === 'in_progress',
                                        'bg-[#006a3d]/15 text-[#006a3d]': row.status === 'submitted',
                                    }"
                                >
                                    {{ row.status === 'not_started' ? 'Not Started' : row.status === 'in_progress' ? 'In Progress' : 'Submitted' }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <p v-if="progress.length === 0" class="p-6 text-center text-[#594048]">No judges configured.</p>
            </div>
        </div>
    </AppLayout>
</template>
