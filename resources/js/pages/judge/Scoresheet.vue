<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { computed, ref, watch, onUnmounted } from 'vue';
import { apiHeaders } from '@/lib/api';
import AppLayout from '@/layouts/AppLayout.vue';
import { useToast } from '@/composables/useToast';
import DecisionModal from '@/components/ui/DecisionModal.vue';
import { Dialog, DialogContent } from '@/components/ui/dialog';
import type { BreadcrumbItem } from '@/types';

const toast = useToast();
const page = usePage<{ auth: { user: { name: string } } }>();
const judgeName = computed(() => page.props.auth?.user?.name ?? 'Judge');

type CriterionCell = { id: number; name: string; max_score: number; current_score: number | null; status: string };
type CategoryBlock = { category: { id: number; name: string; weight: number }; criteria: CriterionCell[] };
type ContestantBlock = { contestant: { id: number; number: string; name: string }; categories: CategoryBlock[] };

type Props = {
    event: { id: number; name: string } | null;
    initialScoresheet?: ContestantBlock[];
};

const props = defineProps<Props>();

const scoresheet = ref<ContestantBlock[]>(props.initialScoresheet ?? []);
const loading = ref(false);
const selectedContestantIndex = ref(0);
const submitAllLoading = ref(false);

function fmt(n: number): string {
    return Number(n).toFixed(2);
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Judge', href: '/judge/dashboard' },
    { title: 'Scoresheet', href: '/judge/scoresheet' },
];

// ---------------------------------------------------------------------------
// Locked-contestant tracking (persisted to localStorage per event)
// ---------------------------------------------------------------------------
const storageKey = computed(() => props.event ? `judge-locked-${props.event.id}` : null);

function loadLockedIds(): Set<number> {
    if (!storageKey.value) return new Set();
    try {
        const raw = localStorage.getItem(storageKey.value);
        if (raw) return new Set(JSON.parse(raw) as number[]);
    } catch { /* ignore corrupt data */ }
    return new Set();
}

function persistLockedIds() {
    if (!storageKey.value) return;
    localStorage.setItem(storageKey.value, JSON.stringify([...lockedContestantIds.value]));
}

const lockedContestantIds = ref<Set<number>>(loadLockedIds());

function isContestantLocked(contestantId: number): boolean {
    return lockedContestantIds.value.has(contestantId);
}

function lockContestant(contestantId: number) {
    lockedContestantIds.value.add(contestantId);
    lockedContestantIds.value = new Set(lockedContestantIds.value);
    persistLockedIds();
}

function unlockContestant(contestantId: number) {
    lockedContestantIds.value.delete(contestantId);
    lockedContestantIds.value = new Set(lockedContestantIds.value);
    persistLockedIds();
}

// When switching tabs, lock the contestant we're leaving
function selectContestant(newIndex: number) {
    const oldBlock = scoresheet.value[selectedContestantIndex.value];
    if (oldBlock && newIndex !== selectedContestantIndex.value) {
        lockContestant(oldBlock.contestant.id);
    }
    selectedContestantIndex.value = newIndex;
}

// ---------------------------------------------------------------------------
// Admin-password unlock modal
// ---------------------------------------------------------------------------
const showPasswordModal = ref(false);
const adminPassword = ref('');
const passwordError = ref('');
const passwordLoading = ref(false);
const pendingUnlockIndex = ref<number | null>(null);

function requestUnlock(index: number) {
    pendingUnlockIndex.value = index;
    adminPassword.value = '';
    passwordError.value = '';
    showPasswordModal.value = true;
}

function cancelPasswordModal() {
    showPasswordModal.value = false;
    pendingUnlockIndex.value = null;
}

async function confirmUnlock() {
    if (!adminPassword.value) { passwordError.value = 'Password is required.'; return; }
    passwordLoading.value = true;
    passwordError.value = '';
    try {
        const r = await fetch('/api/v1/judge/verify-admin-password', {
            method: 'POST',
            credentials: 'include',
            headers: apiHeaders({ method: 'POST' }),
            body: JSON.stringify({ password: adminPassword.value }),
        });
        if (r.ok) {
            const idx = pendingUnlockIndex.value!;
            const block = scoresheet.value[idx];
            if (block) unlockContestant(block.contestant.id);
            selectedContestantIndex.value = idx;
            showPasswordModal.value = false;
            toast.success('Contestant unlocked by admin.');
        } else {
            passwordError.value = 'Invalid admin password.';
        }
    } catch {
        passwordError.value = 'Network error. Please try again.';
    } finally {
        passwordLoading.value = false;
    }
}

// Handle contestant tab click
function onTabClick(index: number) {
    if (index === selectedContestantIndex.value) return;
    const target = scoresheet.value[index];
    if (target && isContestantLocked(target.contestant.id)) {
        requestUnlock(index);
        return;
    }
    selectContestant(index);
}

// ---------------------------------------------------------------------------
// Score saving with debounce (optimistic local update, NO page reload)
// ---------------------------------------------------------------------------
const saveTimers = new Map<string, ReturnType<typeof setTimeout>>();
const savingKeys = ref<Set<string>>(new Set());

onUnmounted(() => { saveTimers.forEach(t => clearTimeout(t)); });

function handleScoreInput(contestantId: number, criterionId: number, maxScore: number, rawValue: string) {
    let num = parseFloat(rawValue);
    if (isNaN(num)) num = 0;
    if (num < 0) num = 0;
    if (num > maxScore) num = maxScore;

    // Optimistic local update
    const block = scoresheet.value.find(b => b.contestant.id === contestantId);
    if (block) {
        for (const cat of block.categories) {
            const cr = cat.criteria.find(c => c.id === criterionId);
            if (cr) { cr.current_score = num; break; }
        }
    }

    // Debounced API save (500ms)
    const key = `${contestantId}-${criterionId}`;
    const existing = saveTimers.get(key);
    if (existing) clearTimeout(existing);
    saveTimers.set(key, setTimeout(() => debouncedSave(contestantId, criterionId, num, key), 500));
}

async function debouncedSave(contestantId: number, criterionId: number, score: number, key: string) {
    if (!props.event) return;
    savingKeys.value.add(key);
    savingKeys.value = new Set(savingKeys.value);
    try {
        const r = await fetch('/api/v1/judge/scores', {
            method: 'POST',
            credentials: 'include',
            headers: apiHeaders({ method: 'POST' }),
            body: JSON.stringify({ event_id: props.event.id, contestant_id: contestantId, criterion_id: criterionId, score }),
        });
        if (!r.ok) {
            const json = await r.json().catch(() => null);
            toast.error(json?.message ?? 'Failed to save score.');
        }
    } catch {
        toast.error('Network error saving score.');
    } finally {
        savingKeys.value.delete(key);
        savingKeys.value = new Set(savingKeys.value);
        saveTimers.delete(key);
    }
}

const isSaving = computed(() => savingKeys.value.size > 0);

// ---------------------------------------------------------------------------
// Refresh scoresheet (manual only, no page reload)
// ---------------------------------------------------------------------------
async function fetchScoresheet() {
    if (!props.event) return;
    loading.value = true;
    try {
        const r = await fetch(`/api/v1/judge/events/${props.event.id}/scoresheet`, {
            credentials: 'include',
            headers: { Accept: 'application/json' },
        });
        if (r.ok) {
            const json = await r.json();
            scoresheet.value = (json.data ?? []) as ContestantBlock[];
        } else {
            toast.error('Could not refresh scoresheet.');
        }
        if (selectedContestantIndex.value >= scoresheet.value.length) selectedContestantIndex.value = 0;
    } finally {
        loading.value = false;
    }
}

// ---------------------------------------------------------------------------
// Submit all scores
// ---------------------------------------------------------------------------
const showSubmitModal = ref(false);

async function submitAll() {
    if (!props.event) return;
    submitAllLoading.value = true;
    try {
        const r = await fetch(`/api/v1/judge/events/${props.event.id}/scores/submit`, {
            method: 'POST',
            credentials: 'include',
            headers: apiHeaders({ method: 'POST', contentType: false }),
        });
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

// ---------------------------------------------------------------------------
// Computed helpers
// ---------------------------------------------------------------------------
// ---------------------------------------------------------------------------
// Standby tab (index === scoresheet.length)
// ---------------------------------------------------------------------------
const STANDBY_INDEX = computed(() => scoresheet.value.length);
const isOnStandby = computed(() => selectedContestantIndex.value === STANDBY_INDEX.value);

function goToStandby() {
    const oldBlock = scoresheet.value[selectedContestantIndex.value];
    if (oldBlock) lockContestant(oldBlock.contestant.id);
    selectedContestantIndex.value = STANDBY_INDEX.value;
}

type StandbySummaryRow = {
    contestant: { id: number; number: string; name: string };
    categoryTotals: { name: string; weight: number; score: number; max: number }[];
    grandTotal: number;
    grandMax: number;
};

const standbySummary = computed<StandbySummaryRow[]>(() => {
    return scoresheet.value.map(block => {
        const categoryTotals = block.categories.map(cat => {
            let score = 0;
            let max = 0;
            for (const cr of cat.criteria) {
                max += Number(cr.max_score);
                score += Number(cr.current_score ?? 0);
            }
            return { name: cat.category.name, weight: cat.category.weight, score, max };
        });
        const grandTotal = categoryTotals.reduce((s, c) => s + c.score, 0);
        const grandMax = categoryTotals.reduce((s, c) => s + c.max, 0);
        return { contestant: block.contestant, categoryTotals, grandTotal, grandMax };
    });
});

// ---------------------------------------------------------------------------
// Computed helpers
// ---------------------------------------------------------------------------
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

const currentContestantLocked = computed(() => {
    if (isOnStandby.value) return false;
    const block = scoresheet.value[selectedContestantIndex.value];
    return block ? isContestantLocked(block.contestant.id) : false;
});

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
                    <span v-if="isSaving" class="flex items-center gap-1.5 text-xs text-amber-400">
                        <span class="inline-block h-3 w-3 animate-spin rounded-full border-2 border-amber-400 border-t-transparent" />
                        Saving…
                    </span>
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
                    <!-- Contestant tabs + Standby tab -->
                    <div class="flex flex-wrap gap-1 border-b border-slate-700 pb-2">
                        <button
                            v-for="(block, i) in scoresheet"
                            :key="block.contestant.id"
                            type="button"
                            class="rounded-t px-4 py-2 text-sm font-medium transition"
                            :class="[
                                selectedContestantIndex === i
                                    ? 'border-b-2 border-[#F23892] text-[#F23892]'
                                    : isContestantLocked(block.contestant.id)
                                        ? 'text-slate-500 hover:text-slate-400'
                                        : 'text-slate-400 hover:text-white',
                            ]"
                            @click="onTabClick(i)"
                        >
                            <span v-if="isContestantLocked(block.contestant.id)" class="mr-1 inline-block text-xs">&#128274;</span>
                            {{ block.contestant.number }} — {{ block.contestant.name }}
                        </button>
                        <!-- Standby tab -->
                        <button
                            v-if="scoresheet.length > 0"
                            type="button"
                            class="rounded-t px-4 py-2 text-sm font-medium transition"
                            :class="isOnStandby
                                ? 'border-b-2 border-[#38F298] text-[#38F298]'
                                : 'text-slate-400 hover:text-white'"
                            @click="goToStandby"
                        >
                            Standby
                        </button>
                    </div>

                    <!-- Locked banner -->
                    <div v-if="currentContestantLocked" class="rounded-xl border border-amber-600/50 bg-amber-900/30 px-4 py-3 text-sm text-amber-300">
                        This contestant's scores are locked. To make changes, click the locked tab and enter the super-admin password.
                    </div>

                    <!-- ========== Standby view: score summary ========== -->
                    <div v-if="isOnStandby" class="space-y-6">
                        <div class="rounded-2xl border border-slate-700 bg-slate-800/80 p-5">
                            <h2 class="mb-1 text-lg font-semibold text-white">Score Summary</h2>
                            <p class="mb-4 text-sm text-slate-400">Review your scores for all contestants before submitting.</p>

                            <div class="overflow-x-auto">
                                <table class="w-full text-left text-sm">
                                    <thead>
                                        <tr class="border-b border-slate-700 text-slate-400">
                                            <th class="pb-3 pr-4 font-medium">#</th>
                                            <th class="pb-3 pr-4 font-medium">Contestant</th>
                                            <th
                                                v-for="cat in (standbySummary[0]?.categoryTotals ?? [])"
                                                :key="cat.name"
                                                class="pb-3 pr-4 text-center font-medium"
                                            >
                                                {{ cat.name }}
                                                <span class="ml-1 text-xs text-slate-500">({{ cat.weight }}%)</span>
                                            </th>
                                            <th class="pb-3 text-center font-semibold text-white">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr
                                            v-for="row in standbySummary"
                                            :key="row.contestant.id"
                                            class="border-b border-slate-700/50"
                                        >
                                            <td class="py-3 pr-4 text-slate-400">{{ row.contestant.number }}</td>
                                            <td class="py-3 pr-4 font-medium text-white">{{ row.contestant.name }}</td>
                                            <td
                                                v-for="cat in row.categoryTotals"
                                                :key="cat.name"
                                                class="py-3 pr-4 text-center"
                                            >
                                                <span class="text-slate-200">{{ fmt(cat.score) }}</span>
                                                <span class="text-slate-500">/{{ fmt(cat.max) }}</span>
                                            </td>
                                            <td class="py-3 text-center font-semibold text-[#38F298]">
                                                {{ fmt(row.grandTotal) }}
                                                <span class="text-slate-500 font-normal">/{{ fmt(row.grandMax) }}</span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <p v-if="standbySummary.length === 0" class="mt-4 text-center text-slate-500">No scores yet.</p>
                        </div>
                    </div>

                    <!-- ========== Contestant scoring view ========== -->
                    <div v-else-if="current" class="space-y-6">
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
                                        v-if="canEdit && !currentContestantLocked && cr.status === 'draft'"
                                        type="number"
                                        :min="0"
                                        :max="cr.max_score"
                                        step="0.01"
                                        :value="cr.current_score ?? ''"
                                        class="score-input w-20 rounded-xl border-2 border-slate-600 bg-slate-800 px-3 py-1.5 text-center text-white transition focus:border-[#F23892] focus:outline-none focus:ring-2 focus:ring-[#F23892]/30"
                                        @change="current && handleScoreInput(current.contestant.id, cr.id, cr.max_score, $event.target.value)"
                                        @blur="current && handleScoreInput(current.contestant.id, cr.id, cr.max_score, $event.target.value)"
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

            <!-- Sticky bottom bar -->
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
                    @click="showSubmitModal = true"
                >
                    {{ submitAllLoading ? 'Submitting…' : 'Submit All Scores' }}
                </button>
            </div>

            <!-- Submit confirmation modal -->
            <DecisionModal
                :open="showSubmitModal"
                title="Submit all scores?"
                message="Once submitted, you won't be able to edit. Continue?"
                confirm-label="Submit"
                cancel-label="Cancel"
                variant="primary"
                :loading="submitAllLoading"
                @confirm="submitAll"
                @cancel="showSubmitModal = false"
            />

            <!-- Admin password modal -->
            <Dialog :open="showPasswordModal" @update:open="(v: boolean) => !v && cancelPasswordModal()">
                <DialogContent
                    :show-close-button="false"
                    class="max-w-[380px] rounded-3xl border-0 p-0 shadow-2xl dark:bg-slate-900"
                    @pointer-down-outside="cancelPasswordModal"
                    @escape-key-down="cancelPasswordModal"
                >
                    <div class="p-6">
                        <h3 class="text-center text-lg font-semibold text-white">Unlock Contestant</h3>
                        <p class="mt-2 text-center text-sm text-slate-400">
                            This contestant's scores are locked. Enter the super-admin password to unlock and edit.
                        </p>
                        <div class="mt-4">
                            <input
                                v-model="adminPassword"
                                type="password"
                                placeholder="Super-admin password"
                                class="w-full rounded-xl border-2 border-slate-600 bg-slate-800 px-4 py-2.5 text-white placeholder-slate-500 transition focus:border-[#F23892] focus:outline-none focus:ring-2 focus:ring-[#F23892]/30"
                                @keyup.enter="confirmUnlock"
                            />
                            <p v-if="passwordError" class="mt-2 text-sm text-red-400">{{ passwordError }}</p>
                        </div>
                    </div>
                    <div class="flex flex-col border-t border-slate-700">
                        <button
                            type="button"
                            class="min-h-[48px] border-b border-slate-700 text-sm font-semibold text-[#F23892] transition active:opacity-80"
                            :disabled="passwordLoading"
                            @click="confirmUnlock"
                        >
                            <span v-if="passwordLoading" class="inline-flex items-center gap-2">
                                <span class="inline-block h-4 w-4 animate-spin rounded-full border-2 border-current border-t-transparent" />
                                Verifying…
                            </span>
                            <span v-else>Unlock</span>
                        </button>
                        <button
                            type="button"
                            class="min-h-[48px] text-sm font-semibold text-slate-400 transition active:opacity-80"
                            :disabled="passwordLoading"
                            @click="cancelPasswordModal"
                        >
                            Cancel
                        </button>
                    </div>
                </DialogContent>
            </Dialog>
        </div>
    </AppLayout>
</template>
