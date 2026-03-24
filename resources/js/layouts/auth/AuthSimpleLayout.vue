<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { home } from '@/routes';
import { ChevronDown, ChevronUp } from 'lucide-vue-next';

defineProps<{
    title?: string;
    description?: string;
}>();

const showDemoCredentials = ref(false);

const demoCredentials = [
    { role: 'Super Admin', email: 'super@admin.com', password: 'admin123' },
    { role: 'Judge', email: 'judge1@tabulation.com', password: 'judge123' },
    { role: 'MC', email: 'mc@tabulation.com', password: 'mc123' },
    { role: 'Organizer', email: 'organizer@tabulation.com', password: 'organizer123' },
];
</script>

<template>
    <div
        class="flex min-h-svh flex-col items-center justify-center gap-6 bg-[#07091A] p-4 text-white md:p-10"
    >
        <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top,rgba(242,56,146,0.12)_0%,transparent_50%)]" />

        <div class="relative w-full max-w-[420px]">
            <div class="flex flex-col gap-6">
                <div class="flex flex-col items-center gap-4">
                    <Link
                        :href="home()"
                        class="flex flex-col items-center gap-2 font-medium"
                    >
                        <div
                            class="mb-1 flex h-11 w-11 items-center justify-center rounded-2xl bg-[#F23892]/20 shadow-lg"
                        >
                            <AppLogoIcon
                                class="size-7 fill-current text-[#F23892]"
                            />
                        </div>
                        <span class="sr-only">{{ title }}</span>
                    </Link>
                    <div class="space-y-1 text-center">
                        <h1 class="text-2xl font-semibold tracking-tight text-white">
                            {{ title }}
                        </h1>
                        <p class="text-center text-sm text-slate-400">
                            {{ description }}
                        </p>
                    </div>
                </div>
                <div
                    class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6 shadow-2xl backdrop-blur md:p-8"
                >
                    <slot />
                </div>
                <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-3 shadow-lg backdrop-blur">
                    <button
                        type="button"
                        class="flex w-full items-center justify-between text-left text-sm font-medium text-slate-300"
                        @click="showDemoCredentials = !showDemoCredentials"
                    >
                        <span>Demo credentials for testers</span>
                        <component :is="showDemoCredentials ? ChevronUp : ChevronDown" class="h-4 w-4 shrink-0 text-slate-500" />
                    </button>
                    <div
                        v-show="showDemoCredentials"
                        class="mt-3 space-y-2 border-t border-slate-700 pt-3 text-xs text-slate-400"
                    >
                        <p
                            v-for="cred in demoCredentials"
                            :key="cred.email"
                            class="font-mono leading-relaxed"
                        >
                            <span class="font-semibold text-slate-300">{{ cred.role }}:</span>
                            {{ cred.email }} / {{ cred.password }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
