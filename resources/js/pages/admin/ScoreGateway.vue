<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { apiHeaders } from '@/lib/api';
import AppLayout from '@/layouts/AppLayout.vue';
import DecisionModal from '@/components/ui/DecisionModal.vue';
import { useToast } from '@/composables/useToast';
import type { BreadcrumbItem } from '@/types';

const toast = useToast();
const showDeliverModal = ref(false);

type Criterion = { id: number; name: string; max_score: number; description: string | null };
type Category = {
    id: number;
    name: string;
    weight: number;
    description: string | null;
    criteria: Criterion[];
};
type Contestant = { id: number; contestant_number: string; name: string };

type EventPayload = {
    id: number;
    name: string;
    event_date: string;
    venue: string | null;
    status: string;
    contestants: Contestant[];
    categories: Category[];
} | null;

const props = defineProps<{ event: EventPayload }>();

const deliverLoading = ref(false);

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: 'Admin', href: '/admin/dashboard' },
    { title: 'Score Gateway', href: '/admin/gateway' },
]);

const totalWeight = computed(() => {
    if (!props.event) return 0;
    return props.event.categories.reduce((sum, c) => sum + Number(c.weight ?? 0), 0);
});

function requestDeliver() {
    if (!props.event) return;

    if (totalWeight.value !== 100) {
        toast.error('Total category weight must be exactly 100% before starting scoring.');
        return;
    }

    if (props.event.contestants.length === 0) {
        toast.error('Add contestants before delivering to judges.');
        return;
    }

    showDeliverModal.value = true;
}

async function confirmDeliver() {
    if (!props.event) return;
    deliverLoading.value = true;
    try {
        const r = await fetch(`/api/v1/admin/events/${props.event.id}/start-scoring`, {
            method: 'POST',
            credentials: 'include',
            headers: apiHeaders({ method: 'POST', contentType: false }),
        });
        const json = await r.json().catch(() => ({}));
        if (r.ok) {
            toast.success('Scoring opened for judges.');
        } else {
            toast.error(json.message || 'Could not start scoring.');
        }
    } finally {
        deliverLoading.value = false;
        showDeliverModal.value = false;
    }
}
</script>

<template>
    <Head title="Score Gateway - Admin" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-semibold text-white">Score Gateway</h1>
                    <p v-if="event" class="mt-1 text-sm text-slate-400">
                        Review event details and scoring system before sending to judges.
                    </p>
                    <p v-else class="mt-1 text-sm text-slate-400">
                        No event in progress. Ask the organizer to submit a scoring system.
                    </p>
                </div>
                <button
                    v-if="event"
                    type="button"
                    class="rounded-full bg-[#F23892] px-5 py-2.5 text-sm font-semibold text-white shadow-[0_0_12px_rgba(242,56,146,0.4)] transition hover:bg-[#d0206e] disabled:opacity-60"
                    :disabled="deliverLoading"
                    @click="requestDeliver"
                >
                    {{ deliverLoading ? 'Delivering…' : 'Deliver to judges' }}
                </button>
            </div>

            <div v-if="event" class="grid gap-4 md:grid-cols-2">
                <section class="rounded-2xl border border-slate-700 bg-slate-900/80 p-5">
                    <h2 class="text-sm font-semibold text-slate-200">Event details</h2>
                    <dl class="mt-3 space-y-2 text-sm text-slate-300">
                        <div class="flex justify-between gap-3">
                            <dt class="text-slate-400">Name</dt>
                            <dd class="font-medium text-white">{{ event.name }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-slate-400">Date</dt>
                            <dd>{{ event.event_date }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-slate-400">Venue</dt>
                            <dd>{{ event.venue || '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-slate-400">Status</dt>
                            <dd class="capitalize">{{ event.status }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-slate-400">Contestants</dt>
                            <dd>{{ event.contestants.length }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-slate-400">Total weight</dt>
                            <dd :class="totalWeight === 100 ? 'text-[#38F298]' : 'text-amber-400'">
                                {{ totalWeight }}%
                            </dd>
                        </div>
                    </dl>
                </section>

                <section class="rounded-2xl border border-slate-700 bg-slate-900/80 p-5">
                    <h2 class="text-sm font-semibold text-slate-200">Contestant list (read-only)</h2>
                    <ul class="mt-3 space-y-1 text-sm text-slate-200">
                        <li
                            v-for="c in event.contestants"
                            :key="c.id"
                            class="flex justify-between rounded-lg bg-slate-800/80 px-3 py-1.5"
                        >
                            <span>{{ c.contestant_number }} — {{ c.name }}</span>
                        </li>
                        <li v-if="event.contestants.length === 0" class="text-slate-400">
                            No contestants for this event.
                        </li>
                    </ul>
                </section>
            </div>

            <section v-if="event" class="rounded-2xl border border-slate-700 bg-slate-900/80 p-5">
                <h2 class="mb-3 text-sm font-semibold text-slate-200">Scoring system (read-only)</h2>
                <div class="grid gap-4 md:grid-cols-2">
                    <div
                        v-for="cat in event.categories"
                        :key="cat.id"
                        class="rounded-2xl border border-slate-700 bg-slate-950/60 p-4"
                    >
                        <div class="flex items-center justify-between">
                            <h3 class="font-medium text-white">{{ cat.name }}</h3>
                            <span class="rounded-full bg-[#BCD1FF]/20 px-3 py-1 text-xs text-[#BCD1FF]">
                                {{ cat.weight }}%
                            </span>
                        </div>
                        <p v-if="cat.description" class="mt-1 text-xs text-slate-400">
                            {{ cat.description }}
                        </p>
                        <ul class="mt-3 space-y-1.5 text-xs text-slate-200">
                            <li
                                v-for="cr in cat.criteria"
                                :key="cr.id"
                                class="flex justify-between rounded-lg bg-slate-900/70 px-3 py-1.5"
                            >
                                <span>{{ cr.name }}</span>
                                <span class="text-slate-400">max {{ cr.max_score }}</span>
                            </li>
                            <li v-if="cat.criteria.length === 0" class="text-slate-500">
                                No criteria defined.
                            </li>
                        </ul>
                    </div>
                </div>
            </section>
        </div>

        <DecisionModal
            :open="showDeliverModal"
            title="Deliver to judges?"
            message="Open scoring for judges using this event, contestants, and scoring system?"
            confirm-label="Deliver"
            cancel-label="Cancel"
            variant="primary"
            :loading="deliverLoading"
            @confirm="confirmDeliver"
            @cancel="showDeliverModal = false"
        />
    </AppLayout>
</template>

