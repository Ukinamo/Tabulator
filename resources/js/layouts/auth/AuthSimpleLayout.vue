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
    { role: 'Super Admin', email: 'superadmin@tabulation.com', password: 'admin123' },
    { role: 'Judge', email: 'judge1@tabulation.com', password: 'judge123' },
    { role: 'MC', email: 'mc@tabulation.com', password: 'mc123' },
    { role: 'Organizer', email: 'organizer@tabulation.com', password: 'organizer123' },
];
</script>

<template>
    <div
        class="neon-stage-gradient relative flex min-h-svh flex-col items-center justify-center gap-6 p-4 text-[#0e193d] md:p-10"
    >
        <div class="pointer-events-none absolute inset-0 overflow-hidden">
            <div class="absolute -left-[10%] -top-[10%] h-[40%] w-[40%] rounded-full bg-white/10 blur-3xl" />
            <div class="absolute -bottom-[5%] -right-[5%] h-[30%] w-[30%] rounded-full bg-[#da2180]/20 blur-3xl" />
        </div>

        <div class="relative w-full max-w-[420px]">
            <div class="flex flex-col gap-6">
                <div class="flex flex-col items-center gap-4">
                    <Link
                        :href="home()"
                        class="flex flex-col items-center gap-2 font-medium"
                    >
                        <div
                            class="mb-1 flex h-11 w-11 items-center justify-center rounded-full bg-white/20 shadow-lg backdrop-blur-sm"
                        >
                            <AppLogoIcon
                                class="size-7 fill-current text-white"
                            />
                        </div>
                        <span class="sr-only">{{ title }}</span>
                    </Link>
                    <div class="space-y-1 text-center">
                        <h1 class="font-headline text-2xl font-bold tracking-tight text-white">
                            {{ title }}
                        </h1>
                        <p class="text-center text-sm text-white/85">
                            {{ description }}
                        </p>
                    </div>
                </div>
                <div
                    class="neon-glass-panel rounded-[1.25rem] p-6 shadow-[0_24px_48px_rgba(14,25,61,0.12)] md:p-8"
                >
                    <slot />
                </div>
                <div class="rounded-2xl border border-white/20 bg-white/10 p-3 shadow-lg backdrop-blur-md">
                    <button
                        type="button"
                        class="flex w-full items-center justify-between text-left text-sm font-medium text-white"
                        @click="showDemoCredentials = !showDemoCredentials"
                    >
                        <span>Demo credentials for testers</span>
                        <component :is="showDemoCredentials ? ChevronUp : ChevronDown" class="h-4 w-4 shrink-0 text-white/70" />
                    </button>
                    <div
                        v-show="showDemoCredentials"
                        class="mt-3 space-y-2 border-t border-white/20 pt-3 text-xs text-white/90"
                    >
                        <p
                            v-for="cred in demoCredentials"
                            :key="cred.email"
                            class="font-mono leading-relaxed"
                        >
                            <span class="font-semibold text-[#ffd9e3]">{{ cred.role }}:</span>
                            {{ cred.email }} / {{ cred.password }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
