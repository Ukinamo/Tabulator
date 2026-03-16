<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { apiHeaders } from '@/lib/api';
import AppLayout from '@/layouts/AppLayout.vue';
import { useToast } from '@/composables/useToast';
import type { BreadcrumbItem } from '@/types';

const toast = useToast();

type EventProp = {
    id: number;
    name: string;
    description: string | null;
    venue: string | null;
    event_date: string;
    status: string;
} | null;

const props = defineProps<{ event: EventProp }>();

const form = ref({
    name: props.event?.name ?? '',
    description: props.event?.description ?? '',
    venue: props.event?.venue ?? '',
    event_date: props.event?.event_date ?? '',
});
const submitLoading = ref(false);
const message = ref('');

watch(() => props.event, (e) => {
    if (e) {
        form.value = { name: e.name, description: e.description ?? '', venue: e.venue ?? '', event_date: e.event_date };
    }
}, { immediate: true });

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: '/admin/dashboard' },
    { title: 'Event Setup', href: '/admin/event' },
];

async function save() {
    if (!props.event) return;
    submitLoading.value = true;
    message.value = '';
    try {
        const r = await fetch(`/api/v1/admin/events/${props.event.id}`, {
            method: 'PUT',
            credentials: 'include',
            headers: apiHeaders({ method: 'PUT' }),
            body: JSON.stringify(form.value),
        });
        const json = await r.json();
        if (r.ok) {
            message.value = 'Saved.';
            toast.success('Event updated.');
        } else {
            message.value = json.message || 'Failed';
            toast.error(message.value);
        }
    } finally {
        submitLoading.value = false;
    }
}
</script>

<template>
    <Head title="Event Setup - Admin" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4">
            <h1 class="text-xl font-semibold text-white">Event Setup</h1>

            <div v-if="!event" class="rounded-2xl border border-slate-700 bg-slate-900/80 p-8 text-center text-slate-400">
                No event found. Create one via API or seed data.
            </div>

            <div v-else class="max-w-2xl space-y-6">
                <div class="rounded-2xl border border-slate-700 bg-slate-900/80 p-6">
                    <p class="mb-3 text-xs text-slate-400">Status (read-only)</p>
                    <span class="inline-flex rounded-full bg-[#BCD1FF]/20 px-3 py-1 text-sm text-[#BCD1FF]">{{ event.status }}</span>
                </div>

                <div class="rounded-2xl border border-slate-700 bg-slate-900/80 p-6">
                    <form class="space-y-4" @submit.prevent="save">
                        <div>
                            <label class="block text-xs text-slate-400">Event name</label>
                            <input v-model="form.name" type="text" required class="mt-1 w-full rounded-lg border border-slate-600 bg-slate-800 px-3 py-2 text-white" />
                        </div>
                        <div>
                            <label class="block text-xs text-slate-400">Description</label>
                            <textarea v-model="form.description" rows="2" class="mt-1 w-full rounded-lg border border-slate-600 bg-slate-800 px-3 py-2 text-white"></textarea>
                        </div>
                        <div>
                            <label class="block text-xs text-slate-400">Venue</label>
                            <input v-model="form.venue" type="text" class="mt-1 w-full rounded-lg border border-slate-600 bg-slate-800 px-3 py-2 text-white" />
                        </div>
                        <div>
                            <label class="block text-xs text-slate-400">Event date</label>
                            <input v-model="form.event_date" type="date" required class="mt-1 w-full rounded-lg border border-slate-600 bg-slate-800 px-3 py-2 text-white" />
                        </div>
                        <p v-if="message" class="text-sm" :class="message === 'Saved.' ? 'text-[#38F298]' : 'text-red-400'">{{ message }}</p>
                        <button type="submit" class="rounded-full bg-[#F23892] px-6 py-2 text-sm font-semibold text-white" :disabled="submitLoading">
                            {{ submitLoading ? 'Saving…' : 'Save' }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
