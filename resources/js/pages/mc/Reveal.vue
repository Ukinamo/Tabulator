<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import AppShell from '@/components/AppShell.vue';
import ResultRevealCard from '@/components/results/ResultRevealCard.vue';
import { apiHeaders } from '@/lib/api';

type RevealedResult = {
    id: number;
    rank: number;
    contestant_name: string;
    contestant_number: string;
    final_score: number | string;
};

const revealedResults = ref<RevealedResult[]>([]);
const hasMore = ref(false);
const isPublished = ref(false);
const eventTitle = ref('Live Results');
const isLoading = ref(false);
const isRevealing = ref(false);
const showCountdown = ref(false);
const countdown = ref(3);
const showConfetti = ref(false);

const fetchResults = async () => {
    isLoading.value = true;

    try {
        const response = await fetch('/api/v1/mc/results', {
            credentials: 'include',
            headers: {
                Accept: 'application/json',
            },
        });

        if (!response.ok) return;

        const payload = await response.json();
        const data = payload.data ?? {};

        revealedResults.value = data.revealed ?? [];
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
            if (result.rank === 1) {
                showConfetti.value = true;
                setTimeout(() => { showConfetti.value = false; }, 5000);
            }
        }

        hasMore.value = Boolean(data.has_more ?? false);
    } finally {
        isRevealing.value = false;
    }
};

onMounted(async () => {
    await fetchResults();
});
</script>

<template>
    <Head title="Live Results Reveal" />

    <AppShell variant="header">
        <div
            class="flex min-h-screen flex-col items-center justify-between bg-[#07091A] px-4 py-6 text-white"
        >
            <header class="flex w-full max-w-5xl items-center justify-between">
                <h1 class="text-xl font-bold tracking-tight text-white">
                    {{ eventTitle }} — Talent Search
                </h1>
                <div class="flex items-center gap-3">
                    <span
                        class="rounded-full bg-[#38F298]/20 px-3 py-1 text-xs font-medium text-[#38F298] ring-1 ring-[#38F298]/40"
                    >
                        MC Mode
                    </span>
                    <Link
                        :href="'/logout'"
                        method="post"
                        as="button"
                        class="rounded-xl border-2 border-[#BCD1FF] bg-transparent px-4 py-2 text-sm font-semibold text-[#BCD1FF] transition hover:bg-[#BCD1FF]/10"
                    >
                        Logout
                    </Link>
                </div>
            </header>

            <main
                class="flex w-full max-w-5xl flex-1 flex-col items-center justify-center gap-6"
            >
                <div v-if="isLoading" class="text-sm text-slate-300">
                    Loading revealed results...
                </div>

                <div
                    v-else-if="!isPublished"
                    class="text-center text-slate-300"
                >
                    <p class="text-sm">
                        Waiting for results...
                    </p>
                </div>

                <div
                    v-else-if="revealedResults.length === 0"
                    class="text-center text-slate-300"
                >
                    <p class="text-sm">Results published. Ready to reveal.</p>
                </div>

                <div
                    v-else
                    class="flex max-h-[60vh] w-full flex-col items-center gap-4 overflow-y-auto pb-4"
                >
                    <ResultRevealCard
                        v-for="(result, index) in revealedResults"
                        :key="result.id"
                        :result="result"
                        :is-new="index === 0"
                    />
                </div>

                <!-- Counter in muted blue -->
                <p
                    v-if="isPublished && revealedResults.length > 0"
                    class="text-sm text-[#BCD1FF]/80"
                >
                    {{ revealedResults.length }} revealed
                    <template v-if="hasMore"> — more remaining</template>
                    <template v-else> — all revealed</template>
                </p>

                <div
                    v-if="showCountdown"
                    class="mt-4 text-5xl font-bold tracking-[0.3em] text-pink-300"
                >
                    {{ countdown || 'Reveal!' }}
                </div>
            </main>

            <footer class="mt-8 flex w-full max-w-5xl flex-col items-center gap-4">
                <button
                    type="button"
                    class="inline-flex items-center justify-center rounded-xl bg-[#F23892] px-12 py-4 text-lg font-semibold tracking-wide text-white shadow-[0_0_28px_rgba(242,56,146,0.6)] transition hover:bg-[#d0206e] hover:shadow-[0_0_32px_rgba(242,56,146,0.7)] disabled:cursor-not-allowed disabled:bg-pink-500/60 disabled:shadow-none"
                    :disabled="!hasMore || isRevealing"
                    @click="revealNext"
                >
                    <span v-if="hasMore">Reveal next winner</span>
                    <span v-else>✓ All winners revealed</span>
                </button>
            </footer>

            <!-- Confetti overlay when winner (rank 1) is revealed -->
            <Teleport to="body">
                <div
                    v-if="showConfetti"
                    class="reveal-confetti-overlay"
                    aria-hidden="true"
                >
                    <div
                        v-for="i in 80"
                        :key="i"
                        class="reveal-confetti-piece"
                        :style="{
                            '--delay': ((i - 1) % 20) * 0.05 + 's',
                            '--left': 30 + ((i - 1) % 40) * 1.25 + '%',
                            '--hue': ((i - 1) * 37) % 360,
                        }"
                    />
                </div>
            </Teleport>
        </div>
    </AppShell>
</template>

<style scoped>
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
    width: 10px;
    height: 10px;
    background: hsl(var(--hue), 85%, 58%);
    animation: reveal-confetti-fall 4s ease-out var(--delay) forwards;
    opacity: 0;
}

@keyframes reveal-confetti-fall {
    0% {
        opacity: 1;
        transform: translateY(0) rotate(0deg);
    }
    100% {
        opacity: 0;
        transform: translateY(110vh) rotate(720deg);
    }
}
</style>

