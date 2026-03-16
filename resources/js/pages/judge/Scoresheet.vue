<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch } from 'vue';
import { apiHeaders } from '@/lib/api';
import AppLayout from '@/layouts/AppLayout.vue';
import { useToast } from '@/composables/useToast';
import DecisionModal from '@/components/ui/DecisionModal.vue';
import type { BreadcrumbItem } from '@/types';

const toast = useToast();
const page = usePage<{ auth: { user: { name: string } } }>();
const judgeName = computed(() => page.props.auth?.user?.name ?? 'Judge');

type CriterionCell = { id: number; name: string; max_score: number; current_score: number | null; status: string };
type CategoryBlock = { category: { id: number; name: string; weight: number }; criteria: CriterionCell[] };
type ContestantBlock = { contestant: { id: number; number: string; name: string }; categories: CategoryBlock[] };

type Props = { event: { id: number; name: string } | null };

const props = defineProps<Props>();

const scoresheet = ref<ContestantBlock[]>([]);
const loading = ref(true);
const selectedContestantIndex = ref(0);
const submitAllLoading = ref(false);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Judge', href: '/judge/dashboard' },
    { title: 'Scoresheet', href: '/judge/scoresheet' },
];

async function fetchScoresheet() {
    if (!props.event) return;
    loading.value = true;
    try {
        const r = await fetch(`/api/v1/judge/events/${props.event.id}/scoresheet`, { credentials: 'include', headers: { Accept: 'application/json' } });
        if (!r.ok) return;
        const json = await r.json();
        scoresheet.value = (json.data ?? []) as ContestantBlock[];
        if (selectedContestantIndex.value >= scoresheet.value.length) selectedContestantIndex.value = 0;
    } finally {
        loading.value = false;
    }
}

async function saveScore(contestantId: number, criterionId: number, score: number) {
    if (!props.event) return;
    await fetch('/api/v1/judge/scores', {
        method: 'POST',
        credentials: 'include',
        headers: apiHeaders({ method: 'POST' }),
        body: JSON.stringify({ event_id: props.event.id, contestant_id: contestantId, criterion_id: criterionId, score }),
    });
    await fetchScoresheet();
}

const showSubmitModal = ref(false);

function openSubmitModal() {
    showSubmitModal.value = true;
}

function closeSubmitModal() {
    showSubmitModal.value = false;
}

async function submitAll() {
    if (!props.event) return;
    submitAllLoading.value = true;
    try {
        const r = await fetch(`/api/v1/judge/events/${props.event.id}/scores/submit`, { method: 'POST', credentials: 'include', headers: apiHeaders({ method: 'POST', contentType: false }) });
        if (r.ok) {
            await fetchScoresheet();
            toast.success('Scores submitted.');
            showSubmitModal.value = false;
        } else {
            toast.error('Could not submit scores.');
        }
    } finally {
        submitAllLoading.value = false;
    }
}

const current = ref<ContestantBlock | null>(null);
watch([scoresheet, selectedContestantIndex], () => {
    current.value = scoresheet.value[selectedContestantIndex.value] ?? null;
}, { immediate: true });

const canEdit = ref(true);
watch(scoresheet, (s) => {
    const first = s[0];
    if (!first) return;
    for (const cat of first.categories) {
        for (const cr of cat.criteria) {
            if (cr.status === 'submitted' || cr.status === 'approved') { canEdit.value = false; return; }
        }
    }
    canEdit.value = true;
}, { immediate: true });

const draftCount = computed(() => {
    let total = 0;
    let filled = 0;
    for (const block of scoresheet.value) {
        for (const cat of block.categories) {
            for (const cr of cat.criteria) {
                total += 1;
                if (cr.current_score != null) filled += 1;
            }
        }
    }
    return { total, filled };
});

watch(() => props.event?.id, (id) => { if (id) fetchScoresheet(); }, { immediate: true });
onMounted(() => { if (props.event) fetchScoresheet(); });
</script>

<template>
    <Head title="Scoresheet - Judge" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex min-h-0 flex-col">
            <!-- Deep navy topbar -->
            <div class="flex shrink-0 items-center justify-between border-b border-slate-700 bg-[#0F1A3E] px-6 py-4">
                <div>
                    <h1 class="text-xl font-semibold text-white">
                        {{ event ? `${event.name} — Scoresheet` : 'Scoresheet' }}
                    </h1>
                    <p class="mt-0.5 text-sm text-slate-300">{{ judgeName }}</p>
                </div>
                <div class="flex items-center gap-3">
                    <button
                        v-if="event && !loading"
                        type="button"
                        class="rounded-lg border border-slate-600 bg-slate-800/80 px-3 py-1.5 text-xs font-medium text-slate-300 transition hover:bg-slate-700 hover:text-white"
                        @click="fetchScoresheet"
                    >
                        Refresh list
                    </button>
                    <span
                        v-if="event && !loading"
                        class="rounded-full bg-[#FFD166]/25 px-4 py-1.5 text-xs font-semibold text-[#FFD166]"
                    >
                        Draft — {{ draftCount.filled }}/{{ draftCount.total }}
                    </span>
                </div>
            </div>

            <div class="flex flex-1 flex-col gap-6 overflow-auto p-4">
                <p v-if="!event" class="text-slate-400">No event selected.</p>
                <div v-else-if="loading" class="rounded-2xl border border-slate-700 bg-slate-800/80 p-8 text-center text-slate-400">Loading…</div>

                <template v-else>
                    <!-- Contestant tabs with pink underline for active -->
                    <div class="flex flex-wrap gap-1 border-b border-slate-700 pb-2">
                        <button
                            v-for="(block, i) in scoresheet"
                            :key="block.contestant.id"
                            type="button"
                            class="rounded-t px-4 py-2 text-sm font-medium transition"
                            :class="selectedContestantIndex === i
                                ? 'border-b-2 border-[#F23892] text-[#F23892]'
                                : 'text-slate-400 hover:text-white'"
                            @click="selectedContestantIndex = i"
                        >
                            {{ block.contestant.number }} — {{ block.contestant.name }}
                        </button>
                    </div>

                    <div v-if="current" class="space-y-6">
                        <div
                            v-for="catBlock in current.categories"
                            :key="catBlock.category.id"
                            class="rounded-2xl border border-slate-700 bg-slate-800/80 p-5"
                        >
                            <div class="mb-3 flex items-center justify-between">
                                <h2 class="font-semibold text-white">
                                    {{ catBlock.category.name }}
                                    <span class="ml-2 rounded-full bg-[#F23892] px-2.5 py-0.5 text-xs font-medium text-white">
                                        {{ catBlock.category.weight }}%
                                    </span>
                                </h2>
                                <span v-if="catBlock.criteria.length" class="text-xs text-slate-400">
                                    Max: {{ catBlock.criteria.reduce((s, c) => s + c.max_score, 0) }} pts
                                </span>
                            </div>
                            <div class="space-y-2">
                                <div
                                    v-for="cr in catBlock.criteria"
                                    :key="cr.id"
                                    class="flex items-center justify-between rounded-xl bg-slate-900/50 px-4 py-2.5"
                                >
                                    <span class="text-slate-200">{{ cr.name }} <span class="text-slate-500">(max {{ cr.max_score }})</span></span>
                                    <input
                                        v-if="canEdit && cr.status === 'draft'"
                                        type="number"
                                        :min="0"
                                        :max="cr.max_score"
                                        step="0.01"
                                        :value="cr.current_score ?? ''"
                                        class="score-input w-20 rounded-xl border-2 border-slate-600 bg-slate-800 px-3 py-1.5 text-white transition focus:border-[#F23892] focus:outline-none focus:ring-2 focus:ring-[#F23892]/30"
                                        @input="saveScore(current!.contestant.id, cr.id, Number(($event.target as HTMLInputElement).value) || 0)"
                                    />
                                    <span v-else class="font-medium text-[#38F298]">{{ cr.current_score ?? '—' }}</span>
                                </div>
                            </div>
                            <p v-if="catBlock.criteria.every(c => c.status !== 'draft')" class="mt-2 text-xs font-medium text-[#38F298]">Scored ✓</p>
                        </div>
                    </div>

                    <p v-if="scoresheet.length === 0" class="text-slate-400">No contestants in this event.</p>
                </template>
            </div>

            <!-- Sticky bottom bar: Save Draft + Submit All Scores -->
            <div
                v-if="event && !loading && scoresheet.length > 0"
                class="sticky bottom-0 flex shrink-0 items-center justify-end gap-3 border-t border-slate-700 bg-slate-900/95 px-6 py-4 backdrop-blur"
            >
                <button
                    type="button"
                    class="rounded-xl border border-slate-600 bg-slate-800 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-slate-700"
                    @click="fetchScoresheet"
                >
                    Save draft
                </button>
                <button
                    type="button"
                    class="rounded-xl bg-[#F23892] px-6 py-2.5 text-sm font-semibold text-white shadow-[0_0_16px_rgba(242,56,146,0.45)] transition hover:bg-[#d0206e] disabled:opacity-60"
                    :disabled="!canEdit || submitAllLoading"
                    @click="openSubmitModal"
                >
                    {{ submitAllLoading ? 'Submitting…' : 'Submit All Scores' }}
                </button>
            </div>

            <DecisionModal
                :open="showSubmitModal"
                title="Submit all scores?"
                message="Once submitted, you won't be able to edit. Continue?"
                confirm-label="Submit"
                cancel-label="Cancel"
                variant="primary"
                :loading="submitAllLoading"
                @confirm="submitAll"
                @cancel="closeSubmitModal"
            />
        </div>
    </AppLayout>
</template>
