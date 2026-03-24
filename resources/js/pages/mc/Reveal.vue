<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import AppShell from '@/components/AppShell.vue';
import ResultRevealCard from '@/components/results/ResultRevealCard.vue';
import DecisionModal from '@/components/ui/DecisionModal.vue';
import { apiHeaders } from '@/lib/api';

type RevealedResult = {
    id: number;
    rank: number;
    contestant_name: string;
    contestant_number: string;
    photo_url?: string | null;
    final_score: number | string;
};

const revealedResults = ref<RevealedResult[]>([]);
const consolationList = ref<RevealedResult[]>([]);
const hasMore = ref(false);
const isPublished = ref(false);
const eventTitle = ref('Live Results');
const isLoading = ref(false);
const isRevealing = ref(false);
const showCountdown = ref(false);
const countdown = ref(3);
const showConfetti = ref(false);
const isClearing = ref(false);
const showConsolation = ref(false);
const consolationRevealed = ref(false);
const showClearModal = ref(false);

const winnersSorted = computed(() =>
    [...revealedResults.value].sort((a, b) => a.rank - b.rank),
);

function medalColor(rank: number): string {
    if (rank === 1) return '#FFD700';
    if (rank === 2) return '#C0C0C0';
    if (rank === 3) return '#CD7F32';
    return '#7eb8ff';
}

const fetchResults = async () => {
    isLoading.value = true;
    try {
        const response = await fetch('/api/v1/mc/results', {
            credentials: 'include',
            headers: { Accept: 'application/json' },
        });
        if (!response.ok) return;
        const payload = await response.json();
        const data = payload.data ?? {};
        revealedResults.value = data.revealed ?? [];
        consolationList.value = data.consolation ?? [];
        hasMore.value = Boolean(data.has_more ?? false);
        isPublished.value = Boolean(data.is_published ?? false);
        eventTitle.value = data.event_name ?? eventTitle.value;
    } finally {
        isLoading.value = false;
    }
};

const revealNext = async () => {
    if (!hasMore.value || isRevealing.value) return;
    showCountdown.value = true;
    countdown.value = 3;
    for (let i = 2; i >= 0; i -= 1) {
        await new Promise((resolve) => setTimeout(resolve, 1000));
        countdown.value = i;
    }
    showCountdown.value = false;
    isRevealing.value = true;
    try {
        const response = await fetch('/api/v1/mc/results/reveal', {
            method: 'POST',
            credentials: 'include',
            headers: apiHeaders({ method: 'POST', contentType: false }),
        });
        if (!response.ok) return;
        const payload = await response.json();
        const data = payload.data ?? {};
        if (data.result) {
            const result = data.result as RevealedResult;
            revealedResults.value = [result, ...revealedResults.value];
            if (result.rank <= 3) {
                showConfetti.value = true;
                setTimeout(() => { showConfetti.value = false; }, result.rank === 1 ? 8000 : 4000);
            }
        }
        hasMore.value = Boolean(data.has_more ?? false);
    } finally {
        isRevealing.value = false;
    }
};

function requestClear() {
    showClearModal.value = true;
}

const confirmClear = async () => {
    isClearing.value = true;
    try {
        const response = await fetch('/api/v1/mc/results/clear', {
            method: 'POST',
            credentials: 'include',
            headers: apiHeaders({ method: 'POST', contentType: false }),
        });
        if (response.ok) {
            revealedResults.value = [];
            hasMore.value = true;
            showConfetti.value = false;
            consolationRevealed.value = false;
        }
    } finally {
        isClearing.value = false;
        showClearModal.value = false;
    }
};

const revealConsolation = () => {
    consolationRevealed.value = true;
    showConsolation.value = true;
};

onMounted(async () => {
    await fetchResults();
});
</script>

<template>
    <Head title="Live Results Reveal" />

    <AppShell variant="header">
        <div class="neon-theatrical-bg flex h-screen flex-col text-white">
            <!-- Header -->
            <header class="flex shrink-0 items-center justify-between border-b border-white/10 px-6 py-4 backdrop-blur-sm">
                <h1 class="font-headline text-xl font-bold tracking-tight text-white">
                    {{ eventTitle }} — Talent Search
                </h1>
                <div class="flex items-center gap-3">
                    <span class="rounded-full bg-[#006a3d]/25 px-3 py-1 text-xs font-medium text-[#57ffa6] ring-1 ring-[#57ffa6]/35">
                        MC Mode
                    </span>
                    <Link
                        :href="'/logout'"
                        method="post"
                        as="button"
                        class="rounded-full border-2 border-white/30 bg-white/5 px-4 py-2 text-sm font-semibold text-white/90 backdrop-blur-sm transition hover:bg-white/10"
                    >
                        Logout
                    </Link>
                </div>
            </header>

            <!-- Main content area -->
            <div class="flex min-h-0 flex-1">
                <!-- ===== Left sidebar: Winner portraits in rank order ===== -->
                <aside
                    v-if="revealedResults.length > 0"
                    class="hidden-scrollbar flex w-48 shrink-0 flex-col gap-3 overflow-y-auto border-r border-white/10 px-3 py-4"
                >
                    <p class="mb-1 text-center text-[10px] font-bold uppercase tracking-widest text-slate-500">
                        Top Winners
                    </p>
                    <div
                        v-for="w in winnersSorted"
                        :key="w.id"
                        class="flex flex-col items-center gap-1.5 rounded-xl p-2 transition"
                    >
                        <div
                            class="relative flex h-16 w-16 items-center justify-center overflow-hidden rounded-full border-[3px]"
                            :style="{ borderColor: medalColor(w.rank) }"
                        >
                            <img
                                v-if="w.photo_url"
                                :src="w.photo_url"
                                :alt="w.contestant_name"
                                class="h-full w-full object-cover"
                            />
                            <span v-else class="text-2xl font-bold" :style="{ color: medalColor(w.rank) }">
                                {{ w.contestant_name?.charAt(0) ?? '?' }}
                            </span>
                        </div>
                        <span
                            class="rounded-full px-2 py-0.5 text-xs font-bold"
                            :style="{ backgroundColor: medalColor(w.rank), color: w.rank <= 2 ? '#000' : '#fff' }"
                        >
                            {{ w.rank === 1 ? 'Champion' : w.rank === 2 ? '2nd' : w.rank === 3 ? '3rd' : w.rank + 'th' }}
                        </span>
                        <p class="text-center text-xs font-medium leading-tight text-slate-300">
                            {{ w.contestant_name }}
                        </p>
                    </div>
                </aside>

                <!-- ===== Center: Grand spotlight reveal cards ===== -->
                <main class="flex min-h-0 flex-1 flex-col items-center justify-center gap-6 px-4">
                    <div v-if="isLoading" class="text-sm text-slate-300">
                        Loading revealed results...
                    </div>

                    <div v-else-if="!isPublished" class="text-center text-slate-300">
                        <p class="text-sm">Waiting for results...</p>
                    </div>

                    <div v-else-if="revealedResults.length === 0" class="text-center text-slate-300">
                        <p class="text-sm">Results published. Ready to reveal the Top 5!</p>
                    </div>

                    <div
                        v-else
                        class="hidden-scrollbar flex max-h-[60vh] w-full flex-col items-center gap-4 overflow-y-auto pb-4"
                    >
                        <ResultRevealCard
                            v-for="(result, index) in revealedResults"
                            :key="result.id"
                            :result="result"
                            :is-new="index === 0 && !isLoading"
                        />
                    </div>

                    <p
                        v-if="isPublished && revealedResults.length > 0"
                        class="text-sm text-[#57ffa6]/90"
                    >
                        {{ revealedResults.length }} of Top 5 revealed
                        <template v-if="hasMore"> — more remaining</template>
                        <template v-else> — all Top 5 revealed!</template>
                    </p>

                    <div
                        v-if="showCountdown"
                        class="mt-4 text-5xl font-bold tracking-[0.3em] text-pink-300"
                    >
                        {{ countdown || 'Reveal!' }}
                    </div>
                </main>

                <!-- ===== Right sidebar: Consolation Prize ===== -->
                <aside
                    v-if="isPublished && consolationList.length > 0"
                    class="hidden-scrollbar flex w-64 shrink-0 flex-col border-l border-white/10 px-3 py-4"
                >
                    <p class="mb-3 text-center text-[10px] font-bold uppercase tracking-widest text-slate-500">
                        Consolation Prize Winners
                    </p>

                    <!-- Toggle button: Reveal / Hide -->
                    <button
                        type="button"
                        class="mx-auto mb-4 inline-flex items-center gap-2 rounded-xl border-2 px-5 py-3 text-sm font-semibold transition"
                        :class="consolationRevealed
                            ? 'border-slate-500/60 bg-slate-500/10 text-slate-400 hover:border-slate-400 hover:text-slate-300'
                            : 'border-amber-500/60 bg-amber-500/10 text-amber-400 hover:border-amber-400 hover:bg-amber-500/20 hover:text-amber-300'"
                        @click="consolationRevealed = !consolationRevealed"
                    >
                        <svg v-if="!consolationRevealed" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7" />
                        </svg>
                        <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-5 0-9.27-3.11-11-7.5a11.72 11.72 0 013.168-4.477M6.343 6.343A9.972 9.972 0 0112 5c5 0 9.27 3.11 11 7.5a11.72 11.72 0 01-4.168 4.477M6.343 6.343L3 3m3.343 3.343l2.829 2.829m4.486 4.486l2.829 2.829M6.343 6.343l11.314 11.314M9.878 9.878a3 3 0 004.243 4.243" />
                        </svg>
                        {{ consolationRevealed ? 'Hide' : 'Reveal All' }}
                    </button>

                    <!-- Batch reveal: all consolation winners shown at once -->
                    <div
                        v-if="consolationRevealed"
                        class="hidden-scrollbar flex flex-1 flex-col gap-3 overflow-y-auto"
                    >
                        <div
                            v-for="(c, idx) in consolationList"
                            :key="c.id"
                            class="consolation-card flex items-center gap-3 rounded-xl border border-amber-500/30 bg-white/10 px-3 py-3 backdrop-blur-sm"
                            :style="{ animationDelay: idx * 120 + 'ms' }"
                        >
                            <!-- Rank badge -->
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border-2 border-amber-500/50 bg-amber-500/10">
                                <span class="text-sm font-bold text-amber-400">
                                    {{ c.rank }}
                                </span>
                            </div>
                            <!-- Info -->
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold text-white">
                                    {{ c.contestant_name }}
                                </p>
                                <p class="text-[11px] text-slate-400">
                                    #{{ c.contestant_number }} · Rank {{ c.rank }}
                                </p>
                            </div>
                            <!-- Score -->
                            <div class="shrink-0 text-right">
                                <p class="text-sm font-bold tabular-nums text-amber-400">
                                    {{ Number(c.final_score).toFixed(4) }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <p v-if="consolationRevealed" class="mt-3 text-center text-[11px] text-slate-500">
                        {{ consolationList.length }} consolation awardee{{ consolationList.length !== 1 ? 's' : '' }}
                    </p>
                </aside>
            </div>

            <!-- Footer -->
            <footer class="flex shrink-0 items-center justify-center gap-4 border-t border-white/10 px-6 py-4 backdrop-blur-sm">
                <button
                    v-if="revealedResults.length > 0"
                    type="button"
                    class="inline-flex items-center justify-center rounded-full border-2 border-white/35 bg-white/5 px-8 py-4 text-lg font-semibold tracking-wide text-white/85 backdrop-blur-sm transition hover:border-white/50 hover:bg-white/10 disabled:cursor-not-allowed disabled:opacity-50"
                    :disabled="isClearing || isRevealing"
                    @click="requestClear"
                >
                    {{ isClearing ? 'Clearing…' : 'Clear' }}
                </button>
                <button
                    type="button"
                    class="inline-flex items-center justify-center rounded-full bg-gradient-to-r from-[#b40066] to-[#da2180] px-12 py-4 text-lg font-semibold tracking-wide text-white shadow-[0_0_28px_rgba(180,0,102,0.5)] transition hover:opacity-95 hover:shadow-[0_0_36px_rgba(180,0,102,0.55)] disabled:cursor-not-allowed disabled:opacity-50 disabled:shadow-none"
                    :disabled="!hasMore || isRevealing"
                    @click="revealNext"
                >
                    <span v-if="hasMore">Reveal next winner</span>
                    <span v-else>All Top 5 revealed</span>
                </button>
            </footer>

            <DecisionModal
                :open="showClearModal"
                title="Clear all reveals?"
                message="Clear all revealed winners? You can re-reveal them afterwards."
                confirm-label="Clear"
                cancel-label="Cancel"
                variant="danger"
                :loading="isClearing"
                @confirm="confirmClear"
                @cancel="showClearModal = false"
            />

            <!-- Confetti overlay for top-3 reveals -->
            <Teleport to="body">
                <div
                    v-if="showConfetti"
                    class="reveal-confetti-overlay"
                    aria-hidden="true"
                >
                    <div
                        v-for="i in 120"
                        :key="i"
                        class="reveal-confetti-piece"
                        :style="{
                            '--delay': ((i - 1) % 30) * 0.04 + 's',
                            '--left': 5 + ((i - 1) % 50) * 1.8 + '%',
                            '--hue': ((i - 1) * 37) % 360,
                            '--size': (6 + (i % 8)) + 'px',
                            '--duration': (3 + (i % 3)) + 's',
                        }"
                    />
                </div>
            </Teleport>
        </div>
    </AppShell>
</template>

<style scoped>
.hidden-scrollbar {
    scrollbar-width: none;
    -ms-overflow-style: none;
}
.hidden-scrollbar::-webkit-scrollbar {
    display: none;
}

/* Consolation card staggered entrance */
.consolation-card {
    animation: consolation-enter 0.5s ease-out both;
}

@keyframes consolation-enter {
    0% {
        opacity: 0;
        transform: translateX(40px) scale(0.95);
    }
    100% {
        opacity: 1;
        transform: translateX(0) scale(1);
    }
}

.reveal-confetti-overlay {
    position: fixed;
    inset: 0;
    pointer-events: none;
    z-index: 9999;
    overflow: hidden;
}

.reveal-confetti-piece {
    position: absolute;
    left: var(--left);
    top: -12px;
    width: var(--size, 10px);
    height: var(--size, 10px);
    background: hsl(var(--hue), 85%, 58%);
    border-radius: 2px;
    animation: reveal-confetti-fall var(--duration, 4s) ease-out var(--delay) forwards;
    opacity: 0;
}

@keyframes reveal-confetti-fall {
    0% {
        opacity: 1;
        transform: translateY(0) rotate(0deg) scale(1);
    }
    20% {
        opacity: 1;
    }
    100% {
        opacity: 0;
        transform: translateY(110vh) rotate(720deg) scale(0.5);
    }
}
</style>
