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
        class="flex min-h-svh flex-col items-center justify-center gap-6 p-6 md:p-10"
        style="background: linear-gradient(180deg, #F23892 0%, #c2185b 40%, #7b1fa2 100%);"
    >
        <div class="w-full max-w-[420px]">
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
                    <div class="space-y-2 text-center">
                        <h1 class="text-xl font-semibold text-white">
                            {{ title }}
                        </h1>
                        <p class="text-center text-sm text-white/90">
                            {{ description }}
                        </p>
                    </div>
                </div>
                <div
                    class="rounded-3xl border-0 bg-white p-8 shadow-2xl"
                >
                    <slot />
                </div>
                <div class="rounded-2xl border border-white/20 bg-white/10 p-3 backdrop-blur">
                    <button
                        type="button"
                        class="flex w-full items-center justify-between text-left text-sm font-medium text-white/95"
                        @click="showDemoCredentials = !showDemoCredentials"
                    >
                        <span>Demo credentials for testers</span>
                        <component :is="showDemoCredentials ? ChevronUp : ChevronDown" class="h-4 w-4 shrink-0" />
                    </button>
                    <div v-show="showDemoCredentials" class="mt-3 space-y-2 border-t border-white/20 pt-3 text-xs text-white/85">
                        <p v-for="cred in demoCredentials" :key="cred.email" class="font-mono">
                            <span class="font-semibold">{{ cred.role }}:</span> {{ cred.email }} / {{ cred.password }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
