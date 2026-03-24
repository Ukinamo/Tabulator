<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import TabulatorDatePicker from '@/components/TabulatorDatePicker.vue';
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
            <h1 class="font-headline text-xl font-semibold text-[#0e193d]">Event Setup</h1>

            <div v-if="!event" class="neon-card border border-[#e8e6f5] p-8 text-center text-[#594048]">
                No event found. Create one via API or seed data.
            </div>

            <div v-else class="max-w-2xl space-y-6">
                <div class="neon-card border border-[#e8e6f5] p-6">
                    <p class="mb-3 text-xs text-[#594048]">Status (read-only)</p>
                    <span class="inline-flex rounded-full bg-[#4a5e86]/12 px-3 py-1 text-sm font-medium text-[#4a5e86]">{{ event.status }}</span>
                </div>

                <div class="neon-card border border-[#e8e6f5] p-6">
                    <form class="space-y-4" @submit.prevent="save">
                        <div>
                            <label class="block text-xs text-[#594048]">Event name</label>
                            <input v-model="form.name" type="text" required class="ig-input mt-1 w-full rounded-xl" />
                        </div>
                        <div>
                            <label class="block text-xs text-[#594048]">Description</label>
                            <textarea v-model="form.description" rows="2" class="ig-input mt-1 w-full rounded-xl"></textarea>
                        </div>
                        <div>
                            <label class="block text-xs text-[#594048]">Venue</label>
                            <input v-model="form.venue" type="text" class="ig-input mt-1 w-full rounded-xl" />
                        </div>
                        <TabulatorDatePicker
                            id="event_date"
                            v-model="form.event_date"
                            label="Event date"
                            name="event_date"
                            required
                        />
                        <p v-if="message" class="text-sm" :class="message === 'Saved.' ? 'font-medium text-[#006a3d]' : 'text-red-600'">{{ message }}</p>
                        <button type="submit" class="neon-btn-primary px-6 py-2 text-sm disabled:opacity-60" :disabled="submitLoading">
                            {{ submitLoading ? 'Saving…' : 'Save' }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
