<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { onMounted, ref, watch } from 'vue';
import { apiHeaders } from '@/lib/api';
import AppLayout from '@/layouts/AppLayout.vue';
import { useToast } from '@/composables/useToast';
import type { BreadcrumbItem } from '@/types';

const toast = useToast();

type ScoreRow = {
    id: number;
    event_id: number;
    judge_id: number;
    judge_name: string;
    contestant_id: number;
    contestant_number: string;
    contestant_name: string;
    criterion_id: number;
    criterion_name: string;
    max_score: number;
    score: number;
    status: string;
};

type Props = { event: { id: number; name: string } | null };

const props = defineProps<Props>();

const scores = ref<ScoreRow[]>([]);
const loading = ref(true);
const approveAllLoading = ref(false);
const deleteTarget = ref<ScoreRow | null>(null);
const showDeleteModal = ref(false);
const deleteLoading = ref(false);

function contestantLabel(s: ScoreRow): string {
    if (s.contestant_number && s.contestant_name) return `${s.contestant_number} — ${s.contestant_name}`;
    if (s.contestant_name) return s.contestant_name;
    return `Contestant #${s.contestant_id}`;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: '/admin/dashboard' },
    { title: 'Score Review', href: '/admin/scores' },
];

async function fetchScores() {
    if (!props.event) return;
    loading.value = true;
    try {
        const r = await fetch(`/api/v1/admin/scores/review?event_id=${props.event.id}`, { credentials: 'include', headers: { Accept: 'application/json' } });
        if (!r.ok) return;
        const json = await r.json();
        scores.value = (json.data ?? []) as ScoreRow[];
    } finally {
        loading.value = false;
    }
}

async function approveOne(scoreId: number) {
    const r = await fetch(`/api/v1/admin/scores/${scoreId}/approve`, {
        method: 'POST',
        credentials: 'include',
        headers: apiHeaders({ method: 'POST', contentType: false }),
    });
    if (r.ok) {
        await fetchScores();
        toast.success('Score approved.');
    } else {
        const json = await r.json().catch(() => ({}));
        toast.error(json.message || 'Could not approve score.');
    }
}

async function approveAll() {
    if (!props.event) return;
    approveAllLoading.value = true;
    try {
        const r = await fetch(`/api/v1/admin/events/${props.event.id}/scores/approve-all`, { method: 'POST', credentials: 'include', headers: apiHeaders({ method: 'POST', contentType: false }) });
        if (r.ok) {
            await fetchScores();
            toast.success('All scores approved.');
        } else {
            toast.error('Could not approve all.');
        }
    } finally {
        approveAllLoading.value = false;
    }
}

function openDelete(s: ScoreRow) {
    deleteTarget.value = s;
    showDeleteModal.value = true;
}

function closeDelete() {
    deleteTarget.value = null;
    showDeleteModal.value = false;
}

async function confirmDelete() {
    if (!deleteTarget.value) return;
    deleteLoading.value = true;
    try {
        const r = await fetch(`/api/v1/admin/scores/${deleteTarget.value.id}`, {
            method: 'DELETE',
            credentials: 'include',
            headers: apiHeaders({ method: 'DELETE', contentType: false }),
        });
        if (r.ok) {
            await fetchScores();
            toast.success('Score deleted.');
            closeDelete();
        } else {
            const json = await r.json().catch(() => ({}));
            toast.error(json.message || 'Could not delete score.');
        }
    } finally {
        deleteLoading.value = false;
    }
}

watch(() => props.event?.id, (id) => { if (id) fetchScores(); }, { immediate: true });
onMounted(() => { if (props.event) fetchScores(); });
</script>

<template>
    <Head title="Score Review - Admin" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4">
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-semibold text-white">Score Review</h1>
                <button
                    v-if="event"
                    type="button"
                    class="rounded-full bg-[#F23892] px-5 py-2.5 text-sm font-semibold text-white shadow-[0_0_12px_rgba(242,56,146,0.4)] transition hover:bg-[#d0206e] disabled:opacity-60"
                    :disabled="approveAllLoading || scores.length === 0"
                    @click="approveAll"
                >
                    {{ approveAllLoading ? 'Processing…' : 'Approve All' }}
                </button>
            </div>

            <p v-if="!event" class="text-slate-400">No event selected.</p>
            <div v-else-if="loading" class="rounded-2xl border border-slate-700 bg-slate-900/80 p-8 text-center text-slate-400">Loading…</div>
            <div v-else class="overflow-x-auto rounded-2xl border border-slate-700 bg-slate-900/80">
                <table class="min-w-full text-left text-sm text-slate-200">
                    <thead class="border-b border-slate-700 bg-slate-800/50 text-xs uppercase">
                        <tr>
                            <th class="px-4 py-3">Judge</th>
                            <th class="px-4 py-3">Contestant</th>
                            <th class="px-4 py-3">Criterion</th>
                            <th class="px-4 py-3">Score</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="s in scores" :key="s.id" class="border-b border-slate-800/70">
                            <td class="px-4 py-3">{{ s.judge_name }}</td>
                            <td class="px-4 py-3">{{ contestantLabel(s) }}</td>
                            <td class="px-4 py-3">{{ s.criterion_name }} (max {{ s.max_score }})</td>
                            <td class="px-4 py-3">{{ s.score }}</td>
                            <td class="px-4 py-3">
                                <span
                                    class="rounded-full px-2.5 py-1 text-xs font-medium"
                                    :class="{
                                        'bg-slate-600/80 text-slate-300': s.status === 'draft',
                                        'bg-[#FFD166]/25 text-[#FFD166]': s.status === 'submitted',
                                        'bg-[#38F298]/20 text-[#38F298]': s.status === 'approved',
                                    }"
                                >
                                    {{ s.status }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap items-center gap-3">
                                    <button
                                        v-if="s.status !== 'approved'"
                                        type="button"
                                        class="text-[#38F298] hover:underline"
                                        @click="approveOne(s.id)"
                                    >
                                        Approve
                                    </button>
                                    <button
                                        type="button"
                                        class="text-red-400 hover:text-red-300 hover:underline"
                                        @click="openDelete(s)"
                                    >
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <p v-if="scores.length === 0" class="p-6 text-center text-slate-400">No submitted scores to review.</p>
            </div>

            <DecisionModal
                :open="showDeleteModal"
                title="Delete this score?"
                :message="deleteTarget ? 'This score will be removed and results recalculated. This cannot be undone.' : ''"
                confirm-label="Delete"
                cancel-label="Cancel"
                variant="danger"
                :loading="deleteLoading"
                @confirm="confirmDelete"
                @cancel="closeDelete"
            />
        </div>
    </AppLayout>
</template>
