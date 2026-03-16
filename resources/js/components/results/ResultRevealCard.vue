<script setup lang="ts">
type Props = {
    result: {
        rank: number;
        contestant_name: string;
        contestant_number: string;
        final_score: number | string;
    };
    isNew?: boolean;
};

defineProps<Props>();
</script>

<template>
    <div
        class="relative w-full max-w-2xl rounded-2xl border-2 bg-slate-900/90 px-6 py-5 shadow-xl transition"
        :class="isNew
            ? 'border-[#38F298] shadow-[0_0_24px_rgba(56,242,152,0.35)]'
            : 'border-[#BCD1FF]/30'"
    >
        <div class="flex items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <!-- Pink circular rank badge -->
                <div
                    class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-[#F23892] text-xl font-bold text-white shadow-lg"
                >
                    {{ result.rank }}
                </div>
                <div>
                    <p
                        v-if="isNew"
                        class="mb-1 text-xs font-medium uppercase tracking-wide text-[#38F298]"
                    >
                        Just revealed
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
                <p class="text-2xl font-bold tabular-nums text-[#38F298]">
                    {{ Number(result.final_score).toFixed(4) }}
                </p>
                <p class="text-xs font-medium text-[#38F298]/80">final score</p>
            </div>
        </div>
    </div>
</template>

