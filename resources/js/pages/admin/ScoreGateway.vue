<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { apiHeaders } from '@/lib/api';
import AppLayout from '@/layouts/AppLayout.vue';
import DecisionModal from '@/components/ui/DecisionModal.vue';
import { useToast } from '@/composables/useToast';
import type { BreadcrumbItem } from '@/types';

const toast = useToast();
const showDeliverModal = ref(false);
const showClearModal = ref(false);
const showRetrieveModal = ref(false);

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
const clearLoading = ref(false);
const retrieveLoading = ref(false);

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
            toast.success((json as { message?: string }).message || 'Scoring opened for judges.');
            router.reload({ only: ['event'] });
        } else {
            toast.error((json as { message?: string }).message || 'Could not start scoring.');
        }
    } finally {
        deliverLoading.value = false;
        showDeliverModal.value = false;
    }
}

function requestClear() {
    if (!props.event) return;
    showClearModal.value = true;
}

function requestRetrieve() {
    if (!props.event) return;
    showRetrieveModal.value = true;
}

async function confirmClear() {
    if (!props.event) return;
    clearLoading.value = true;
    try {
        const r = await fetch(`/api/v1/admin/events/${props.event.id}/clear-gateway`, {
            method: 'POST',
            credentials: 'include',
            headers: apiHeaders({ method: 'POST', contentType: false }),
        });
        const json = await r.json().catch(() => ({}));
        if (r.ok) {
            toast.success((json as { message?: string }).message || 'Score Gateway cleared.');
            router.reload({ only: ['event'] });
        } else {
            toast.error((json as { message?: string }).message || 'Could not clear gateway.');
        }
    } finally {
        clearLoading.value = false;
        showClearModal.value = false;
    }
}

async function confirmRetrieve() {
    if (!props.event) return;
    retrieveLoading.value = true;
    try {
        const r = await fetch(`/api/v1/admin/events/${props.event.id}/retrieve-scoring`, {
            method: 'POST',
            credentials: 'include',
            headers: apiHeaders({ method: 'POST', contentType: false }),
        });
        const json = await r.json().catch(() => ({}));
        if (r.ok) {
            toast.success((json as { message?: string }).message || 'Scoring retrieved from judges.');
            router.reload({ only: ['event'] });
        } else {
            toast.error((json as { message?: string }).message || 'Could not retrieve scoring from judges.');
        }
    } finally {
        retrieveLoading.value = false;
        showRetrieveModal.value = false;
    }
}
</script>

<template>
    <Head title="Score Gateway - Admin" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="font-headline text-xl font-semibold text-[#0e193d]">Score Gateway</h1>
                    <p v-if="event" class="mt-1 text-sm text-[#594048]">
                        Review event details and scoring system before sending to judges.
                    </p>
                    <p v-else class="mt-1 text-sm text-[#594048]">
                        No event in progress. Ask the organizer to submit a scoring system.
                    </p>
                </div>
                <div
                    v-if="event"
                    class="flex flex-shrink-0 flex-wrap items-center justify-end gap-3"
                >
                    <button
                        type="button"
                        class="rounded-full border border-[#e0bec7] bg-white px-5 py-2.5 text-sm font-semibold text-[#594048] shadow-sm transition hover:bg-[#f3f2ff] disabled:opacity-60 dark:border-white/15 dark:bg-[#0e193d]/80 dark:text-slate-200 dark:hover:bg-white/10"
                        :disabled="clearLoading || deliverLoading || retrieveLoading"
                        @click="requestClear"
                    >
                        {{ clearLoading ? 'Clearing…' : 'Clear all' }}
                    </button>
                    <button
                        type="button"
                        class="rounded-full border border-border bg-card px-5 py-2.5 text-sm font-semibold text-foreground shadow-sm transition hover:bg-muted disabled:opacity-60"
                        :disabled="deliverLoading || clearLoading || retrieveLoading || event?.status !== 'scoring'"
                        @click="requestRetrieve"
                    >
                        {{ retrieveLoading ? 'Retrieving…' : 'Retrieve from judges' }}
                    </button>
                    <button
                        type="button"
                        class="neon-btn-primary px-5 py-2.5 text-sm disabled:opacity-60"
                        :disabled="deliverLoading || clearLoading || retrieveLoading"
                        @click="requestDeliver"
                    >
                        {{ deliverLoading ? 'Delivering…' : 'Deliver to judges' }}
                    </button>
                </div>
            </div>

            <div v-if="event" class="grid gap-4 md:grid-cols-2">
                <section class="neon-card border border-[#e8e6f5] p-5">
                    <h2 class="text-sm font-semibold text-[#0e193d]">Event details</h2>
                    <dl class="mt-3 space-y-2 text-sm text-[#594048]">
                        <div class="flex justify-between gap-3">
                            <dt class="text-[#4a5e86]">Name</dt>
                            <dd class="font-medium text-[#0e193d]">{{ event.name }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-[#4a5e86]">Date</dt>
                            <dd class="text-[#0e193d]">{{ event.event_date }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-[#4a5e86]">Venue</dt>
                            <dd class="text-[#0e193d]">{{ event.venue || '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-[#4a5e86]">Status</dt>
                            <dd class="capitalize text-[#0e193d]">{{ event.status }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-[#4a5e86]">Contestants</dt>
                            <dd class="text-[#0e193d]">{{ event.contestants.length }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-[#4a5e86]">Total weight</dt>
                            <dd :class="totalWeight === 100 ? 'font-semibold text-[#006a3d]' : 'font-semibold text-amber-600'">
                                {{ totalWeight }}%
                            </dd>
                        </div>
                    </dl>
                </section>

                <section class="neon-card border border-[#e8e6f5] p-5">
                    <h2 class="text-sm font-semibold text-[#0e193d]">Contestant list (read-only)</h2>
                    <ul class="mt-3 space-y-1 text-sm text-[#0e193d]">
                        <li
                            v-for="c in event.contestants"
                            :key="c.id"
                            class="flex justify-between rounded-xl bg-[#ebedff] px-3 py-1.5"
                        >
                            <span>{{ c.contestant_number }} — {{ c.name }}</span>
                        </li>
                        <li v-if="event.contestants.length === 0" class="text-[#594048]">
                            No contestants for this event.
                        </li>
                    </ul>
                </section>
            </div>

            <section v-if="event" class="neon-card border border-border p-5">
                <h2 class="mb-3 text-sm font-semibold text-foreground">Scoring system (read-only)</h2>
                <div class="grid gap-4 md:grid-cols-2">
                    <div
                        v-for="cat in event.categories"
                        :key="cat.id"
                        class="neon-card-alt rounded-2xl border border-border p-4"
                    >
                        <div class="flex items-center justify-between">
                            <h3 class="font-medium text-foreground">{{ cat.name }}</h3>
                            <span class="rounded-full bg-secondary px-3 py-1 text-xs font-medium text-secondary-foreground">
                                {{ cat.weight }}%
                            </span>
                        </div>
                        <p v-if="cat.description" class="mt-1 text-xs text-muted-foreground">
                            {{ cat.description }}
                        </p>
                        <ul class="mt-3 space-y-1.5 text-xs text-foreground">
                            <li
                                v-for="cr in cat.criteria"
                                :key="cr.id"
                                class="flex justify-between rounded-lg bg-input px-3 py-1.5 shadow-sm"
                            >
                                <span>{{ cr.name }}</span>
                                <span class="text-muted-foreground">max {{ cr.max_score }}</span>
                            </li>
                            <li v-if="cat.criteria.length === 0" class="text-muted-foreground">
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

        <DecisionModal
            :open="showClearModal"
            title="Clear Score Gateway?"
            message="This removes the event from the Score Gateway and returns it to the organizer for edits. If scoring was already open for judges, all judge scores and computed results for this event will be permanently removed."
            confirm-label="Clear all"
            cancel-label="Cancel"
            variant="danger"
            :loading="clearLoading"
            @confirm="confirmClear"
            @cancel="showClearModal = false"
        />

        <DecisionModal
            :open="showRetrieveModal"
            title="Retrieve scoring from judges?"
            message="This will pull back the delivered scoring from judges and clear all judge scores/results for this event. The event will return to admin review."
            confirm-label="Retrieve"
            cancel-label="Cancel"
            variant="danger"
            :loading="retrieveLoading"
            @confirm="confirmRetrieve"
            @cancel="showRetrieveModal = false"
        />
    </AppLayout>
</template>

