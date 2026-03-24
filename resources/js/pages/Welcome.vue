<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { dashboard, login, register } from '@/routes';

withDefaults(
    defineProps<{
        canRegister: boolean;
    }>(),
    {
        canRegister: true,
    },
);

const features = [
    {
        icon: 'clipboard',
        title: 'Organizer Setup',
        desc: 'Define scoring categories, criteria, and weights. Set up contestants and event details in minutes.',
    },
    {
        icon: 'shield',
        title: 'Admin Gateway',
        desc: 'Review and approve scoring systems before delivering to judges. Full control over the event workflow.',
    },
    {
        icon: 'pencil',
        title: 'Judge Scoresheet',
        desc: 'Intuitive scoring interface with real-time validation, auto-lock, and a standby summary view.',
    },
    {
        icon: 'chart',
        title: 'Score Review',
        desc: 'Detailed and summary views of all submitted scores. Approve, delete, or manage individual entries.',
    },
    {
        icon: 'star',
        title: 'Live MC Reveal',
        desc: 'Dramatic one-by-one winner reveal with countdown, confetti, medal borders, and champion animations.',
    },
    {
        icon: 'gift',
        title: 'Consolation Awards',
        desc: 'Top 5 get the grand spotlight. Remaining contestants receive consolation prizes with a batch reveal.',
    },
];

const roles = [
    { name: 'Super Admin', color: '#b40066', desc: 'Full system access, user management, event oversight' },
    { name: 'Organizer', color: '#4a5e86', desc: 'Event setup, categories, criteria, contestant management' },
    { name: 'Judge', color: '#006a3d', desc: 'Score contestants with validated input and auto-locking' },
    { name: 'MC', color: '#da2180', desc: 'Live result reveal ceremony with dramatic animations' },
];

const steps = [
    { num: '01', title: 'Create Event', desc: 'Organizer sets up the event, adds contestants, and defines scoring categories with criteria.' },
    { num: '02', title: 'Admin Review', desc: 'Admin reviews the scoring system on the Score Gateway, then delivers it to judges.' },
    { num: '03', title: 'Judges Score', desc: 'Judges input scores per contestant per criterion. Scores auto-lock when moving to the next contestant.' },
    { num: '04', title: 'Publish Results', desc: 'Admin reviews all scores, approves them, and publishes final results for the MC.' },
    { num: '05', title: 'Live Reveal', desc: 'MC reveals winners one by one — 5th to Champion — with countdown, confetti, and trophy animations.' },
];
</script>

<template>
    <Head title="Event Tabulation System" />
    <div class="min-h-screen bg-[#faf8ff] antialiased">
        <!-- Navigation -->
        <nav class="fixed top-0 z-50 w-full border-b border-[#e0bec7]/30 bg-[#faf8ff]/85 backdrop-blur-xl">
            <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
                <div class="flex items-center gap-3">
                    <div class="flex h-9 w-9 items-center justify-center rounded-full bg-[#da2180]/15 shadow-[0_0_12px_rgba(180,0,102,0.2)]">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[#b40066]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                    <span class="welcome-title font-headline text-lg font-bold tracking-tight">Tabulator</span>
                </div>
                <div class="flex items-center gap-3">
                    <Link
                        v-if="$page.props.auth.user"
                        :href="dashboard()"
                        class="rounded-full bg-[#006a3d]/10 px-5 py-2 text-sm font-medium text-[#006a3d] ring-1 ring-[#006a3d]/25 transition hover:bg-[#006a3d]/15"
                    >
                        Dashboard
                    </Link>
                    <template v-else>
                        <Link
                            :href="login()"
                            class="welcome-nav-link rounded-full px-5 py-2 text-sm font-medium transition hover:bg-[#f3f2ff]"
                        >
                            Log in
                        </Link>
                        <Link
                            v-if="canRegister"
                            :href="register()"
                            class="welcome-chip-link rounded-full bg-[#4a5e86]/14 px-5 py-2 text-sm font-semibold ring-1 ring-[#4a5e86]/35 transition hover:bg-[#4a5e86]/20"
                        >
                            Register
                        </Link>
                    </template>
                </div>
            </div>
        </nav>

        <!-- Hero -->
        <section class="relative overflow-hidden pb-20 pt-32">
            <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top,rgba(180,0,102,0.12)_0%,transparent_55%)]" />
            <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_bottom_right,rgba(0,106,61,0.08)_0%,transparent_45%)]" />
            <div class="relative mx-auto max-w-6xl px-6 text-center">
                <div class="mx-auto mb-6 inline-flex items-center gap-2 rounded-full bg-[#ffd9e3]/80 px-4 py-1.5 text-xs font-medium text-[#b40066] ring-1 ring-[#b40066]/20">
                    <span class="h-1.5 w-1.5 rounded-full bg-[#b40066]" />
                    Event Tabulation System
                </div>
                <h1 class="welcome-title font-headline mx-auto max-w-3xl text-4xl font-bold leading-tight tracking-tight sm:text-5xl lg:text-6xl">
                    Score, tabulate, and reveal winners
                    <span class="bg-gradient-to-r from-[#b40066] to-[#4a5e86] bg-clip-text text-transparent">
                        in real time
                    </span>
                </h1>
                <p class="welcome-muted mx-auto mt-6 max-w-2xl text-lg leading-relaxed">
                    A complete scoring platform for pageants, talent shows, and competitions.
                    From organizer setup to dramatic live MC reveals — everything in one system.
                </p>
                <div class="mt-10 flex flex-wrap items-center justify-center gap-4">
                    <Link
                        :href="login()"
                        class="neon-btn-primary inline-flex items-center gap-2 px-8 py-3.5 text-sm"
                    >
                        Get Started
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                        </svg>
                    </Link>
                    <a
                        href="#features"
                        class="welcome-chip-link inline-flex items-center gap-2 rounded-full bg-[#4a5e86]/12 px-8 py-3.5 text-sm font-semibold transition hover:bg-[#4a5e86]/20"
                    >
                        Learn More
                    </a>
                </div>
            </div>
        </section>

        <!-- How it works -->
        <section class="welcome-light-section border-t border-[#e0bec7]/25 py-20">
            <div class="mx-auto max-w-6xl px-6">
                <div class="text-center">
                    <p class="text-xs font-bold uppercase tracking-widest text-[#006a3d]">Workflow</p>
                    <h2 class="welcome-title font-headline mt-2 text-3xl font-bold tracking-tight">How it works</h2>
                    <p class="welcome-muted mt-3">Five simple steps from event creation to winner reveal</p>
                </div>
                <div class="mt-14 grid gap-6 md:grid-cols-5">
                    <div
                        v-for="step in steps"
                        :key="step.num"
                        class="welcome-light-card relative rounded-2xl p-5 shadow-[0_4px_24px_rgba(14,25,61,0.06)]"
                    >
                        <span class="text-3xl font-black text-[#b40066]/15">{{ step.num }}</span>
                        <h3 class="welcome-title mt-2 text-sm font-semibold">{{ step.title }}</h3>
                        <p class="welcome-muted mt-1.5 text-xs leading-relaxed">{{ step.desc }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features -->
        <section id="features" class="border-t border-[#e0bec7]/25 py-20">
            <div class="mx-auto max-w-6xl px-6">
                <div class="text-center">
                    <p class="text-xs font-bold uppercase tracking-widest text-[#b40066]">Features</p>
                    <h2 class="welcome-title font-headline mt-2 text-3xl font-bold tracking-tight">Everything you need</h2>
                    <p class="welcome-muted mt-3">Built for organizers, judges, admins, and MCs</p>
                </div>
                <div class="mt-14 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="f in features"
                        :key="f.title"
                        class="welcome-light-card group rounded-2xl p-6 shadow-[0_4px_24px_rgba(14,25,61,0.06)] transition hover:shadow-[0_8px_32px_rgba(14,25,61,0.1)]"
                    >
                        <div class="mb-4 flex h-11 w-11 items-center justify-center rounded-xl bg-[#ffd9e3]/80 text-[#b40066] transition group-hover:bg-[#ffd9e3]">
                            <!-- Clipboard -->
                            <svg v-if="f.icon === 'clipboard'" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                            <!-- Shield -->
                            <svg v-else-if="f.icon === 'shield'" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                            <!-- Pencil -->
                            <svg v-else-if="f.icon === 'pencil'" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                            <!-- Chart -->
                            <svg v-else-if="f.icon === 'chart'" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                            <!-- Star -->
                            <svg v-else-if="f.icon === 'star'" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                            </svg>
                            <!-- Gift -->
                            <svg v-else-if="f.icon === 'gift'" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7" />
                            </svg>
                        </div>
                        <h3 class="welcome-title text-base font-semibold">{{ f.title }}</h3>
                        <p class="welcome-muted mt-2 text-sm leading-relaxed">{{ f.desc }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Roles -->
        <section class="welcome-light-section border-t border-[#e0bec7]/25 py-20">
            <div class="mx-auto max-w-6xl px-6">
                <div class="text-center">
                    <p class="text-xs font-bold uppercase tracking-widest text-[#4a5e86]">Multi-role</p>
                    <h2 class="welcome-title font-headline mt-2 text-3xl font-bold tracking-tight">Built for every role</h2>
                    <p class="welcome-muted mt-3">Each user has a purpose-built interface tailored to their responsibilities</p>
                </div>
                <div class="mt-14 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    <div
                        v-for="r in roles"
                        :key="r.name"
                        class="welcome-light-card rounded-2xl p-6 shadow-[0_4px_24px_rgba(14,25,61,0.06)]"
                    >
                        <div
                            class="mb-4 inline-flex rounded-full px-3 py-1 text-xs font-bold"
                            :style="{ backgroundColor: r.color + '1a', color: r.color }"
                        >
                            {{ r.name }}
                        </div>
                        <p class="welcome-muted text-sm leading-relaxed">{{ r.desc }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA -->
        <section class="border-t border-[#e0bec7]/25 py-20">
            <div class="mx-auto max-w-6xl px-6 text-center">
                <h2 class="welcome-title font-headline text-3xl font-bold tracking-tight">Ready to tabulate?</h2>
                <p class="welcome-muted mt-3">Set up your event and start scoring in minutes.</p>
                <div class="mt-8 flex flex-wrap items-center justify-center gap-4">
                    <Link
                        :href="login()"
                        class="neon-btn-primary inline-flex items-center gap-2 px-8 py-3.5 text-sm"
                    >
                        Log in to start
                    </Link>
                    <Link
                        v-if="canRegister"
                        :href="register()"
                        class="welcome-chip-link inline-flex items-center gap-2 rounded-full bg-[#4a5e86]/12 px-8 py-3.5 text-sm font-semibold transition hover:bg-[#4a5e86]/20"
                    >
                        Create an account
                    </Link>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="border-t border-[#e0bec7]/25 py-8">
            <div class="mx-auto max-w-6xl px-6">
                <div class="flex flex-col items-center justify-between gap-4 sm:flex-row">
                    <div class="welcome-muted flex items-center gap-2 text-sm">
                        <div class="flex h-6 w-6 items-center justify-center rounded-lg bg-[#ffd9e3]/80">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-[#b40066]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                        Tabulator — Event Tabulation System
                    </div>
                    <p class="welcome-muted text-xs">Built with Laravel, Vue.js & Inertia.js</p>
                </div>
            </div>
        </footer>
    </div>
</template>

<style scoped>
.welcome-light-section {
    background-color: #f8f6ff;
}

.welcome-light-card {
    background-color: #ffffff;
    border: 1px solid #e8e6f5;
}

.welcome-title {
    color: #0e193d !important;
}

.welcome-muted {
    color: #4f4361 !important;
}

.welcome-nav-link {
    color: #31446f !important;
}

.welcome-chip-link {
    color: #243965 !important;
}
</style>
