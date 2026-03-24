<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch } from 'vue';
import { apiHeaders } from '@/lib/api';
import AppLayout from '@/layouts/AppLayout.vue';
import DecisionModal from '@/components/ui/DecisionModal.vue';
import { useToast } from '@/composables/useToast';
import type { BreadcrumbItem } from '@/types';

const toast = useToast();
const showDeleteAllModal = ref(false);
const showDeleteOneModal = ref(false);
const deleteOneTarget = ref<ScoreRow | null>(null);

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
    category_id: number;
    category_name: string;
    category_weight: number;
    max_score: number;
    score: number;
    status: string;
};

type Props = { event: { id: number; name: string } | null };

const props = defineProps<Props>();

const scores = ref<ScoreRow[]>([]);
const loading = ref(true);
const approveAllLoading = ref(false);
const deleteAllLoading = ref(false);
const deleteLoading = ref(false);
const activeTab = ref<'detailed' | 'summary'>('detailed');

function contestantLabel(s: ScoreRow): string {
    if (s.contestant_number && s.contestant_name) return `${s.contestant_number} — ${s.contestant_name}`;
    if (s.contestant_name) return s.contestant_name;
    return `Contestant #${s.contestant_id}`;
}

function fmt(n: number): string {
    return Number(n).toFixed(2);
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: '/admin/dashboard' },
    { title: 'Score Review', href: '/admin/scores' },
];

// ---------------------------------------------------------------------------
// Summary computed
// ---------------------------------------------------------------------------
type CategoryInfo = { id: number; name: string; weight: number };
type ContestantSummary = {
    contestant_id: number;
    contestant_number: string;
    contestant_name: string;
    categories: Record<number, { score: number; max: number }>;
    grandTotal: number;
    grandMax: number;
};

const categories = computed<CategoryInfo[]>(() => {
    const map = new Map<number, CategoryInfo>();
    for (const s of scores.value) {
        if (s.category_id && !map.has(s.category_id)) {
            map.set(s.category_id, { id: s.category_id, name: s.category_name, weight: s.category_weight });
        }
    }
    return [...map.values()];
});

const summaryRows = computed<ContestantSummary[]>(() => {
    const map = new Map<number, ContestantSummary>();

    for (const s of scores.value) {
        let row = map.get(s.contestant_id);
        if (!row) {
            row = {
                contestant_id: s.contestant_id,
                contestant_number: s.contestant_number,
                contestant_name: s.contestant_name,
                categories: {},
                grandTotal: 0,
                grandMax: 0,
            };
            map.set(s.contestant_id, row);
        }
        if (!row.categories[s.category_id]) {
            row.categories[s.category_id] = { score: 0, max: 0 };
        }
        row.categories[s.category_id].score += Number(s.score);
        row.categories[s.category_id].max += Number(s.max_score);
    }

    const rows = [...map.values()];
    for (const row of rows) {
        row.grandTotal = Object.values(row.categories).reduce((sum, c) => sum + c.score, 0);
        row.grandMax = Object.values(row.categories).reduce((sum, c) => sum + c.max, 0);
    }
    rows.sort((a, b) => (a.contestant_number ?? '').localeCompare(b.contestant_number ?? '', undefined, { numeric: true }));
    return rows;
});

// ---------------------------------------------------------------------------
// API calls
// ---------------------------------------------------------------------------
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

function requestDeleteAll() {
    if (!props.event) return;
    showDeleteAllModal.value = true;
}

async function confirmDeleteAll() {
    if (!props.event) return;
    deleteAllLoading.value = true;
    try {
        const r = await fetch(`/api/v1/admin/events/${props.event.id}/scores/delete-all`, {
            method: 'DELETE',
            credentials: 'include',
            headers: apiHeaders({ method: 'DELETE', contentType: false }),
        });
        if (r.ok) {
            await fetchScores();
            toast.success('All scores deleted.');
        } else {
            const json = await r.json().catch(() => ({}));
            toast.error(json.message || 'Could not delete all scores.');
        }
    } finally {
        deleteAllLoading.value = false;
        showDeleteAllModal.value = false;
    }
}

function requestDeleteScore(row: ScoreRow) {
    deleteOneTarget.value = row;
    showDeleteOneModal.value = true;
}

async function confirmDeleteScore() {
    if (!deleteOneTarget.value) return;
    deleteLoading.value = true;
    try {
        const r = await fetch(`/api/v1/admin/scores/${deleteOneTarget.value.id}`, {
            method: 'DELETE',
            credentials: 'include',
            headers: apiHeaders({ method: 'DELETE', contentType: false }),
        });
        if (r.ok) {
            scores.value = scores.value.filter((s) => s.id !== deleteOneTarget.value!.id);
            toast.success('Score deleted.');
        } else {
            const json = await r.json().catch(() => ({}));
            toast.error(json.message || 'Could not delete score.');
        }
    } finally {
        deleteLoading.value = false;
        showDeleteOneModal.value = false;
        deleteOneTarget.value = null;
    }
}

watch(() => props.event?.id, (id) => { if (id) fetchScores(); }, { immediate: true });
onMounted(() => { if (props.event) fetchScores(); });
</script>

<template>
    <Head title="Score Review - Admin" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <h1 class="font-headline text-xl font-semibold text-[#0e193d]">Score Review</h1>
                <div v-if="event" class="flex items-center gap-3">
                    <button
                        type="button"
                        class="rounded-full bg-red-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700 disabled:opacity-60"
                        :disabled="deleteAllLoading || scores.length === 0"
                        @click="requestDeleteAll"
                    >
                        {{ deleteAllLoading ? 'Deleting…' : 'Delete All' }}
                    </button>
                    <button
                        type="button"
                        class="neon-btn-primary px-5 py-2.5 text-sm disabled:opacity-60"
                        :disabled="approveAllLoading || scores.length === 0"
                        @click="approveAll"
                    >
                        {{ approveAllLoading ? 'Processing…' : 'Approve All' }}
                    </button>
                </div>
            </div>

            <!-- Tab switcher -->
            <div v-if="event" class="flex gap-1 border-b border-[#e8e6f5]">
                <button
                    type="button"
                    class="px-5 py-2.5 text-sm font-medium transition"
                    :class="activeTab === 'detailed'
                        ? 'border-b-2 border-[#b40066] text-[#b40066]'
                        : 'text-[#594048] hover:text-[#0e193d]'"
                    @click="activeTab = 'detailed'"
                >
                    Detailed View
                </button>
                <button
                    type="button"
                    class="px-5 py-2.5 text-sm font-medium transition"
                    :class="activeTab === 'summary'
                        ? 'border-b-2 border-[#006a3d] text-[#006a3d]'
                        : 'text-[#594048] hover:text-[#0e193d]'"
                    @click="activeTab = 'summary'"
                >
                    Summary View
                </button>
            </div>

            <p v-if="!event" class="text-[#594048]">No event selected.</p>
            <div v-else-if="loading" class="neon-card border border-[#e8e6f5] p-8 text-center text-[#594048]">Loading…</div>

            <!-- ============ DETAILED VIEW ============ -->
            <div v-else-if="activeTab === 'detailed'" class="neon-card overflow-x-auto border border-[#e8e6f5]">
                <table class="min-w-full text-left text-sm text-[#0e193d]">
                    <thead class="border-b border-[#e8e6f5] bg-[#f3f2ff] text-xs uppercase text-[#594048]">
                        <tr>
                            <th class="px-4 py-3">Judge</th>
                            <th class="px-4 py-3">Contestant</th>
                            <th class="px-4 py-3">Category</th>
                            <th class="px-4 py-3">Criterion</th>
                            <th class="px-4 py-3">Score</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="s in scores" :key="s.id" class="border-b border-[#ebedff] transition hover:bg-[#ebedff]/50">
                            <td class="px-4 py-3">{{ s.judge_name }}</td>
                            <td class="px-4 py-3">{{ contestantLabel(s) }}</td>
                            <td class="px-4 py-3">
                                <span class="text-[#0e193d]">{{ s.category_name }}</span>
                                <span class="ml-1 text-xs text-[#594048]">({{ s.category_weight }}%)</span>
                            </td>
                            <td class="px-4 py-3">{{ s.criterion_name }} (max {{ s.max_score }})</td>
                            <td class="px-4 py-3">{{ s.score }}</td>
                            <td class="px-4 py-3">
                                <span
                                    class="rounded-full px-2.5 py-1 text-xs font-medium"
                                    :class="{
                                        'bg-[#4a5e86]/15 text-[#4a5e86]': s.status === 'draft',
                                        'bg-[#ffd166]/30 text-amber-900': s.status === 'submitted',
                                        'bg-[#006a3d]/15 text-[#006a3d]': s.status === 'approved',
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
                                        class="font-medium text-[#006a3d] hover:underline"
                                        @click="approveOne(s.id)"
                                    >
                                        Approve
                                    </button>
                                    <button
                                        type="button"
                                        class="text-red-600 hover:text-red-700 hover:underline"
                                        @click="requestDeleteScore(s)"
                                    >
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <p v-if="scores.length === 0" class="p-6 text-center text-[#594048]">No submitted scores to review.</p>
            </div>

            <!-- ============ SUMMARY VIEW ============ -->
            <div v-else-if="activeTab === 'summary'" class="space-y-4">
                <div class="neon-card overflow-x-auto border border-[#e8e6f5]">
                    <table class="min-w-full text-left text-sm text-[#0e193d]">
                        <thead class="border-b border-[#e8e6f5] bg-[#f3f2ff]">
                            <tr>
                                <th class="px-4 py-3 text-xs font-medium uppercase text-[#594048]">#</th>
                                <th class="px-4 py-3 text-xs font-medium uppercase text-[#594048]">Contestant</th>
                                <th
                                    v-for="cat in categories"
                                    :key="cat.id"
                                    class="px-4 py-3 text-center text-xs font-medium uppercase text-[#594048]"
                                >
                                    {{ cat.name }}
                                    <span class="ml-1 normal-case text-[#594048]/80">({{ cat.weight }}%)</span>
                                </th>
                                <th class="px-4 py-3 text-center text-xs font-semibold uppercase text-[#0e193d]">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="row in summaryRows"
                                :key="row.contestant_id"
                                class="border-b border-[#ebedff] transition hover:bg-[#ebedff]/50"
                            >
                                <td class="px-4 py-3 text-[#594048]">{{ row.contestant_number }}</td>
                                <td class="px-4 py-3 font-medium">{{ row.contestant_name }}</td>
                                <td
                                    v-for="cat in categories"
                                    :key="cat.id"
                                    class="px-4 py-3 text-center"
                                >
                                    <template v-if="row.categories[cat.id]">
                                        <span>{{ fmt(row.categories[cat.id].score) }}</span>
                                        <span class="text-[#594048]">/{{ fmt(row.categories[cat.id].max) }}</span>
                                    </template>
                                    <span v-else class="text-[#594048]/50">—</span>
                                </td>
                                <td class="px-4 py-3 text-center font-semibold text-[#006a3d]">
                                    {{ fmt(row.grandTotal) }}
                                    <span class="font-normal text-[#594048]">/{{ fmt(row.grandMax) }}</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <p v-if="summaryRows.length === 0" class="p-6 text-center text-[#594048]">No submitted scores to summarize.</p>
                </div>

                <!-- Per-contestant breakdown cards -->
                <div v-for="row in summaryRows" :key="row.contestant_id" class="neon-card border border-[#e8e6f5] p-5">
                    <div class="mb-3 flex items-center justify-between">
                        <h3 class="font-semibold text-[#0e193d]">
                            {{ row.contestant_number }} — {{ row.contestant_name }}
                        </h3>
                        <span class="rounded-full bg-[#006a3d]/12 px-3 py-1 text-xs font-semibold text-[#006a3d]">
                            Total: {{ fmt(row.grandTotal) }}/{{ fmt(row.grandMax) }}
                        </span>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        <div
                            v-for="cat in categories"
                            :key="cat.id"
                            class="rounded-xl border border-[#ebedff] bg-[#f3f2ff]/80 p-3"
                        >
                            <div class="mb-1.5 flex items-center justify-between">
                                <span class="text-sm font-medium text-[#0e193d]">{{ cat.name }}</span>
                                <span class="rounded-full bg-[#b40066]/12 px-2 py-0.5 text-xs font-medium text-[#b40066]">{{ cat.weight }}%</span>
                            </div>
                            <div v-if="row.categories[cat.id]" class="flex items-baseline gap-1">
                                <span class="text-2xl font-bold text-[#0e193d]">{{ fmt(row.categories[cat.id].score) }}</span>
                                <span class="text-sm text-[#594048]">/{{ fmt(row.categories[cat.id].max) }}</span>
                            </div>
                            <span v-else class="text-sm text-[#594048]">No scores</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <DecisionModal
            :open="showDeleteAllModal"
            title="Delete all scores?"
            message="Delete ALL submitted/approved scores for this event? This cannot be undone."
            confirm-label="Delete All"
            cancel-label="Cancel"
            variant="danger"
            :loading="deleteAllLoading"
            @confirm="confirmDeleteAll"
            @cancel="showDeleteAllModal = false"
        />

        <DecisionModal
            :open="showDeleteOneModal"
            title="Delete this score?"
            :message="deleteOneTarget ? `Delete the score by ${deleteOneTarget.judge_name} for ${deleteOneTarget.contestant_name} (${deleteOneTarget.criterion_name})? This cannot be undone.` : ''"
            confirm-label="Delete"
            cancel-label="Cancel"
            variant="danger"
            :loading="deleteLoading"
            @confirm="confirmDeleteScore"
            @cancel="showDeleteOneModal = false; deleteOneTarget = null"
        />
    </AppLayout>
</template>
