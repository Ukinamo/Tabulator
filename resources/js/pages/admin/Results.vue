<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { onMounted, ref, watch } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import DecisionModal from '@/components/ui/DecisionModal.vue';
import { useToast } from '@/composables/useToast';
import type { BreadcrumbItem } from '@/types';

const toast = useToast();

type ResultRow = {
    id: number;
    rank: number;
    contestant_id: number;
    contestant_number: string;
    contestant_name: string;
    final_score: number;
    is_published: boolean;
    is_revealed: boolean;
};

type Props = { event: { id: number; name: string } | null };

const props = defineProps<Props>();

const results = ref<ResultRow[]>([]);
const loading = ref(true);
const publishLoading = ref(false);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: '/admin/dashboard' },
    { title: 'Results', href: '/admin/results' },
];

async function fetchResults() {
    if (!props.event) return;
    loading.value = true;
    try {
        const r = await fetch(`/api/v1/admin/results/${props.event.id}`, {
            credentials: 'include',
            headers: { Accept: 'application/json' },
        });
        if (!r.ok) return;
        const json = await r.json();
        results.value = (json.data ?? []) as ResultRow[];
    } finally {
        loading.value = false;
    }
}

const showPublishModal = ref(false);

function openPublishModal() {
    showPublishModal.value = true;
}

function closePublishModal() {
    showPublishModal.value = false;
}
const isPublished = ref(false);

async function confirmPublish() {
    if (!props.event) return;
    publishLoading.value = true;
    try {
        const r = await fetch(`/api/v1/admin/events/${props.event.id}/publish`, {
            method: 'POST',
            credentials: 'include',
            headers: apiHeaders({ method: 'POST', contentType: false }),
        });
        if (!r.ok) {
            const json = await r.json();
            toast.error(json.message || 'Publish failed');
            return;
        }
        await fetchResults();
        toast.success('Results published.');
        showPublishModal.value = false;
    } finally {
        publishLoading.value = false;
    }
}

watch(
    results,
    (r) => {
        isPublished.value = r.some((x) => x.is_published);
    },
    { immediate: true },
);

watch(
    () => props.event?.id,
    (id) => {
        if (id) fetchResults();
    },
    { immediate: true },
);
onMounted(() => {
    if (props.event) fetchResults();
});
</script>

<template>
    <Head title="Results - Admin" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4">
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-semibold text-white">Results</h1>
                <template v-if="event && !isPublished">
                    <button
                        type="button"
                        class="rounded-full bg-[#F23892] px-4 py-2 text-sm font-semibold text-white"
                        :disabled="publishLoading"
                        @click="openPublishModal"
                    >
                        {{ publishLoading ? 'Publishing…' : 'Publish Results' }}
                    </button>
                </template>
                <div v-else-if="event && isPublished" class="rounded-full bg-[#38F298]/20 px-4 py-2 text-sm font-medium text-[#38F298]">
                    Results Published
                </div>
            </div>
            <DecisionModal
                :open="showPublishModal"
                title="Publish results?"
                message="Results will be visible to the MC and can be revealed one at a time. Continue?"
                confirm-label="Publish"
                cancel-label="Cancel"
                variant="primary"
                :loading="publishLoading"
                @confirm="confirmPublish"
                @cancel="closePublishModal"
            />

            <p v-if="!event" class="text-slate-400">No event selected.</p>
            <div v-else-if="loading" class="rounded-2xl border border-slate-700 bg-slate-900/80 p-8 text-center text-slate-400">
                Loading…
            </div>
            <div v-else class="overflow-hidden rounded-2xl border border-slate-700 bg-slate-900/80">
                <table class="min-w-full text-left text-sm text-slate-200">
                    <thead class="border-b border-slate-700 bg-slate-800/50 text-xs uppercase">
                        <tr>
                            <th class="px-4 py-3">Rank</th>
                            <th class="px-4 py-3">#</th>
                            <th class="px-4 py-3">Contestant</th>
                            <th class="px-4 py-3">Final Score</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in results" :key="row.id" class="border-b border-slate-800/70">
                            <td class="px-4 py-3 font-medium">{{ row.rank }}</td>
                            <td class="px-4 py-3">
                                <span class="rounded-full bg-[#F23892]/30 px-2 py-0.5 text-[#F23892]">{{ row.contestant_number }}</span>
                            </td>
                            <td class="px-4 py-3">{{ row.contestant_name }}</td>
                            <td class="px-4 py-3">{{ Number(row.final_score).toFixed(4) }}</td>
                        </tr>
                    </tbody>
                </table>
                <p v-if="results.length === 0" class="p-6 text-center text-slate-400">No results yet. Approve scores and publish.</p>
            </div>
        </div>
    </AppLayout>
</template>
