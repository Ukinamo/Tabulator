<script setup lang="ts">
import { computed } from 'vue';

type Props = {
    result: {
        rank: number;
        contestant_name: string;
        contestant_number: string;
        final_score: number | string;
    };
    isNew?: boolean;
};

const props = defineProps<Props>();

const medal = computed(() => {
    if (props.result.rank === 1) return { label: 'Champion', border: 'border-[#FFD700]', shadow: 'shadow-[0_0_32px_rgba(255,215,0,0.5)]', badge: 'bg-[#FFD700] text-black', glow: true };
    if (props.result.rank === 2) return { label: '2nd Place', border: 'border-[#C0C0C0]', shadow: 'shadow-[0_0_24px_rgba(192,192,192,0.4)]', badge: 'bg-[#C0C0C0] text-black', glow: false };
    if (props.result.rank === 3) return { label: '3rd Place', border: 'border-[#CD7F32]', shadow: 'shadow-[0_0_24px_rgba(205,127,50,0.4)]', badge: 'bg-[#CD7F32] text-white', glow: false };
    return null;
});

const cardClasses = computed(() => {
    if (medal.value) return `${medal.value.border} ${medal.value.shadow}`;
    if (props.isNew) return 'border-[#57ffa6]/50 shadow-[0_0_24px_rgba(0,106,61,0.35)]';
    return 'border-white/25';
});

const badgeClasses = computed(() => {
    if (medal.value) return medal.value.badge;
    return 'bg-gradient-to-br from-[#b40066] to-[#da2180] text-white';
});
</script>

<template>
    <div
        class="relative w-full max-w-2xl rounded-2xl border-2 bg-white/[0.94] px-6 py-5 text-[#0e193d] shadow-xl backdrop-blur-md transition dark:bg-[#0e193d]/95 dark:text-white"
        :class="[cardClasses, { 'champion-glow': medal?.glow }]"
    >
        <!-- Trophy for champion -->
        <div v-if="result.rank === 1 && isNew" class="trophy-container">
            <div class="trophy-icon">&#127942;</div>
        </div>

        <div class="flex items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div
                    class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full text-xl font-bold shadow-lg"
                    :class="badgeClasses"
                >
                    {{ result.rank }}
                </div>
                <div>
                    <p
                        v-if="isNew && !medal"
                        class="mb-1 text-xs font-medium uppercase tracking-wide text-[#006a3d] dark:text-[#57ffa6]"
                    >
                        Just revealed
                    </p>
                    <p
                        v-else-if="medal && isNew"
                        class="mb-1 text-xs font-bold uppercase tracking-wide"
                        :class="{
                            'text-[#FFD700]': result.rank === 1,
                            'text-[#C0C0C0]': result.rank === 2,
                            'text-[#CD7F32]': result.rank === 3,
                        }"
                    >
                        {{ medal.label }}
                    </p>
                    <h2 class="text-xl font-semibold text-white">
                        {{ result.contestant_name }}
                    </h2>
                    <p class="text-sm text-slate-400">
                        Contestant No. {{ result.contestant_number }}
                    </p>
                </div>
            </div>
            <div class="flex flex-col items-end">
                <p
                    class="text-2xl font-bold tabular-nums"
                    :class="{
                        'text-[#FFD700]': result.rank === 1,
                        'text-[#C0C0C0]': result.rank === 2,
                        'text-[#CD7F32]': result.rank === 3,
                        'text-[#38F298]': result.rank > 3,
                    }"
                >
                    {{ Number(result.final_score).toFixed(4) }}
                </p>
                <p class="text-xs font-medium text-[#594048] dark:text-slate-400">final score</p>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Gold pulsing glow for champion */
.champion-glow {
    animation: champion-pulse 2s ease-in-out infinite;
}

@keyframes champion-pulse {
    0%, 100% {
        box-shadow: 0 0 32px rgba(255, 215, 0, 0.4);
    }
    50% {
        box-shadow: 0 0 56px rgba(255, 215, 0, 0.7), 0 0 80px rgba(255, 215, 0, 0.3);
    }
}

/* Trophy animation */
.trophy-container {
    position: absolute;
    top: -32px;
    right: 24px;
    pointer-events: none;
    z-index: 10;
}

.trophy-icon {
    font-size: 48px;
    animation: trophy-entrance 1s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
    filter: drop-shadow(0 0 12px rgba(255, 215, 0, 0.8));
}

@keyframes trophy-entrance {
    0% {
        opacity: 0;
        transform: scale(0) rotate(-30deg) translateY(20px);
    }
    50% {
        opacity: 1;
        transform: scale(1.3) rotate(10deg) translateY(-8px);
    }
    70% {
        transform: scale(0.95) rotate(-5deg) translateY(2px);
    }
    100% {
        opacity: 1;
        transform: scale(1) rotate(0deg) translateY(0);
    }
}
</style>
