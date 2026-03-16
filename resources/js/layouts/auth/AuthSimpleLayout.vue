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
        class="flex min-h-svh flex-col items-center justify-center gap-6 p-4 md:p-10"
        style="background: radial-gradient(circle at top, rgba(255, 255, 255, 0.18) 0, transparent 55%), linear-gradient(180deg, #F23892 0%, #c2185b 40%, #7b1fa2 100%);"
    >
        <div class="w-full max-w-[420px] drop-shadow-2xl">
            <div class="flex flex-col gap-8">
                <div class="flex flex-col items-center gap-4">
                    <Link
                        :href="home()"
                        class="flex flex-col items-center gap-2 font-medium"
                    >
                        <div
                            class="mb-1 flex h-11 w-11 items-center justify-center rounded-2xl bg-[#F23892] shadow-lg"
                        >
                            <AppLogoIcon
                                class="size-7 fill-current text-white"
                            />
                        </div>
                        <span class="sr-only">{{ title }}</span>
                    </Link>
                    <div class="space-y-1 text-center">
                        <h1 class="text-2xl font-semibold tracking-tight">
                            {{ title }}
                        </h1>
                        <p class="text-center text-sm text-white">
                            {{ description }}
                        </p>
                    </div>
                </div>
                <div
                    class="rounded-3xl border border-white/40 bg-grey/95 p-6 md:p-8 shadow-2xl backdrop-blur"
                >
                    <slot />
                </div>
                <div class="rounded-2xl border border-white/70 bg-grey/95 p-3 shadow-lg backdrop-blur">
                    <button
                        type="button"
                        class="flex w-full items-center justify-between text-left text-sm font-medium text-slate-900"
                        @click="showDemoCredentials = !showDemoCredentials"
                    >
                        <span>Demo credentials for testers</span>
                        <component :is="showDemoCredentials ? ChevronUp : ChevronDown" class="h-4 w-4 shrink-0" />
                    </button>
                    <div
                        v-show="showDemoCredentials"
                        class="mt-3 space-y-2 border-t border-slate-200 pt-3 text-xs text-black/800"
                    >
                        <p
                            v-for="cred in demoCredentials"
                            :key="cred.email"
                            class="font-mono leading-relaxed"
                        >
                            <span class="font-semibold">{{ cred.role }}:</span>
                            {{ cred.email }} / {{ cred.password }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
