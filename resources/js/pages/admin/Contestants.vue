<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { onMounted, ref, watch } from 'vue';
import { apiHeaders } from '@/lib/api';
import AppLayout from '@/layouts/AppLayout.vue';
import DecisionModal from '@/components/ui/DecisionModal.vue';
import { useToast } from '@/composables/useToast';
import type { BreadcrumbItem } from '@/types';

const toast = useToast();

type Contestant = {
    id: number;
    contestant_number: string;
    name: string;
    bio: string | null;
    photo_url: string | null;
    is_active: boolean;
};

type Props = { event: { id: number; name: string } | null };

const props = defineProps<Props>();

const contestants = ref<Contestant[]>([]);
const loading = ref(true);
const showModal = ref(false);
const editing = ref<Contestant | null>(null);
const form = ref({ contestant_number: '', name: '', bio: '', photo_url: '', is_active: true });
const submitLoading = ref(false);
const error = ref('');
const photoUploading = ref(false);
const photoInput = ref<HTMLInputElement | null>(null);

async function onPhotoSelected(event: Event) {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];
    if (!file || !props.event) return;
    photoUploading.value = true;
    error.value = '';
    try {
        const fd = new FormData();
        fd.append('photo', file);
        const r = await fetch(`/api/v1/admin/events/${props.event.id}/contestants/upload-photo`, {
            method: 'POST',
            credentials: 'include',
            headers: apiHeaders({ method: 'POST', contentType: false }),
            body: fd,
        });
        const json = await r.json();
        if (!r.ok) {
            toast.error(json.message || 'Photo upload failed.');
            return;
        }
        form.value.photo_url = json.data?.url ?? '';
        toast.success('Photo uploaded.');
    } finally {
        photoUploading.value = false;
        input.value = '';
    }
}

function clearPhoto() {
    form.value.photo_url = '';
}

function fullPhotoUrl(url: string | null): string {
    if (!url) return '';
    if (url.startsWith('http') || url.startsWith('/')) return url;
    return `${window.location.origin}${url.startsWith('/') ? '' : '/'}${url}`;
}

function hideBrokenPhoto(e: Event) {
    const el = e.target as HTMLImageElement;
    if (el) el.style.display = 'none';
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: '/admin/dashboard' },
    { title: 'Contestants', href: '/admin/contestants' },
];

async function fetchContestants() {
    if (!props.event) return;
    loading.value = true;
    try {
        const r = await fetch(`/api/v1/admin/events/${props.event.id}/contestants`, { credentials: 'include', headers: { Accept: 'application/json' } });
        if (!r.ok) return;
        const json = await r.json();
        contestants.value = (json.data ?? []) as Contestant[];
    } finally {
        loading.value = false;
    }
}

watch(() => props.event?.id, (id) => { if (id) fetchContestants(); }, { immediate: true });
onMounted(() => { if (props.event) fetchContestants(); });

function openCreate() {
    editing.value = null;
    form.value = { contestant_number: '', name: '', bio: '', photo_url: '', is_active: true };
    error.value = '';
    showModal.value = true;
}

function openEdit(c: Contestant) {
    editing.value = c;
    form.value = { contestant_number: c.contestant_number, name: c.name, bio: c.bio ?? '', photo_url: c.photo_url ?? '', is_active: c.is_active };
    error.value = '';
    showModal.value = true;
}

async function save() {
    if (!props.event) return;
    submitLoading.value = true;
    error.value = '';
    try {
        const url = editing.value
            ? `/api/v1/admin/events/${props.event.id}/contestants/${editing.value.id}`
            : `/api/v1/admin/events/${props.event.id}/contestants`;
        const r = await fetch(url, {
            method: editing.value ? 'PUT' : 'POST',
            credentials: 'include',
            headers: apiHeaders({ method: editing.value ? 'PUT' : 'POST' }),
            body: JSON.stringify(form.value),
        });
        const json = await r.json();
        if (!r.ok) {
            error.value = json.message || 'Request failed';
            toast.error(error.value);
            return;
        }
        showModal.value = false;
        await fetchContestants();
        toast.success(editing.value ? 'Contestant updated.' : 'Contestant added.');
    } finally {
        submitLoading.value = false;
    }
}

const deleteTarget = ref<Contestant | null>(null);
const showDeleteModal = ref(false);
const deleteLoading = ref(false);

function openDelete(c: Contestant) {
    deleteTarget.value = c;
    showDeleteModal.value = true;
}

function closeDelete() {
    deleteTarget.value = null;
    showDeleteModal.value = false;
}

async function confirmDelete() {
    if (!props.event || !deleteTarget.value) return;
    deleteLoading.value = true;
    try {
        const r = await fetch(`/api/v1/admin/events/${props.event.id}/contestants/${deleteTarget.value.id}`, { method: 'DELETE', credentials: 'include', headers: apiHeaders({ method: 'DELETE', contentType: false }) });
        if (r.ok) {
            await fetchContestants();
            toast.success('Contestant removed.');
            closeDelete();
        } else {
            const json = await r.json();
            toast.error(json.message || 'Cannot delete.');
        }
    } finally {
        deleteLoading.value = false;
    }
}
</script>

<template>
    <Head title="Contestants - Admin" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4">
            <div class="flex items-center justify-between">
                <h1 class="font-headline text-xl font-semibold text-[#0e193d]">Contestants</h1>
                <button
                    v-if="event"
                    type="button"
                    class="neon-btn-primary px-4 py-2 text-sm"
                    @click="openCreate"
                >
                    Add Contestant
                </button>
            </div>

            <p v-if="!event" class="text-[#594048]">No event selected.</p>
            <div v-else-if="loading" class="neon-card border border-[#e8e6f5] p-8 text-center text-[#594048]">Loading…</div>
            <div v-else class="neon-card overflow-hidden border border-[#e8e6f5]">
                <table class="min-w-full text-left text-sm text-[#0e193d]">
                    <thead class="border-b border-[#e8e6f5] bg-[#f3f2ff] text-xs uppercase text-[#594048]">
                        <tr>
                            <th class="px-4 py-3">#</th>
                            <th class="px-4 py-3">Name</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="c in contestants" :key="c.id" class="border-b border-[#ebedff] transition hover:bg-[#ebedff]/50">
                            <td class="px-4 py-3">
                                <span class="rounded-full bg-[#b40066]/12 px-2 py-0.5 text-xs font-medium text-[#b40066]">{{ c.contestant_number }}</span>
                            </td>
                            <td class="px-4 py-3">{{ c.name }}</td>
                            <td class="px-4 py-3">{{ c.is_active ? 'Active' : 'Inactive' }}</td>
                            <td class="px-4 py-3">
                                <button type="button" class="mr-2 text-[#4a5e86] hover:underline" @click="openEdit(c)">Edit</button>
                                <button type="button" class="text-red-600 hover:underline" @click="openDelete(c)">Delete</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <p v-if="contestants.length === 0" class="p-6 text-center text-[#594048]">No contestants yet.</p>
            </div>
        </div>

        <Teleport to="body">
            <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4 backdrop-blur-sm" @click.self="showModal = false">
                <div class="neon-glass-panel w-full max-w-md rounded-2xl border border-[#e8e6f5] p-6 shadow-2xl">
                    <h2 class="mb-4 font-headline text-lg font-semibold text-[#0e193d]">{{ editing ? 'Edit Contestant' : 'Add Contestant' }}</h2>
                    <p v-if="error" class="mb-3 text-sm text-red-600">{{ error }}</p>
                    <form class="space-y-3" @submit.prevent="save">
                        <div>
                            <label class="block text-xs text-[#594048]">Number</label>
                            <input v-model="form.contestant_number" type="text" required class="ig-input mt-1 w-full rounded-xl" />
                        </div>
                        <div>
                            <label class="block text-xs text-[#594048]">Name</label>
                            <input v-model="form.name" type="text" required class="ig-input mt-1 w-full rounded-xl" />
                        </div>
                        <div>
                            <label class="block text-xs text-[#594048]">Bio</label>
                            <textarea v-model="form.bio" rows="2" class="ig-input mt-1 w-full rounded-xl"></textarea>
                        </div>
                        <div>
                            <label class="block text-xs text-[#594048]">Photo</label>
                            <div class="mt-1 flex flex-col gap-2">
                                <div class="flex items-center gap-2">
                                    <input
                                        ref="photoInput"
                                        type="file"
                                        accept="image/*"
                                        class="hidden"
                                        @change="onPhotoSelected"
                                    />
                                    <button
                                        type="button"
                                        class="rounded-full border border-[#e0d8e8] bg-white px-3 py-2 text-sm text-[#0e193d] transition hover:bg-[#f3f2ff]"
                                        :disabled="photoUploading"
                                        @click="photoInput?.click()"
                                    >
                                        {{ photoUploading ? 'Uploading…' : (form.photo_url ? 'Change photo' : 'Choose photo') }}
                                    </button>
                                    <button
                                        v-if="form.photo_url"
                                        type="button"
                                        class="rounded-full border border-[#e0d8e8] bg-[#f3f2ff] px-3 py-2 text-sm text-[#594048] transition hover:bg-[#ebedff]"
                                        @click="clearPhoto"
                                    >
                                        Remove
                                    </button>
                                </div>
                                <img
                                    v-if="form.photo_url"
                                    :src="fullPhotoUrl(form.photo_url)"
                                    alt="Contestant photo"
                                    class="h-24 w-24 rounded-lg border border-[#e8e6f5] object-cover"
                                    @error="hideBrokenPhoto"
                                />
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <input v-model="form.is_active" type="checkbox" class="rounded border-[#e0d8e8] text-[#b40066] focus:ring-[#b40066]" />
                            <label class="text-sm text-[#0e193d]">Active</label>
                        </div>
                        <div class="flex justify-end gap-2 pt-4">
                            <button type="button" class="rounded-full border border-[#e0d8e8] bg-white px-4 py-2 text-sm text-[#0e193d] hover:bg-[#f3f2ff]" @click="showModal = false">Cancel</button>
                            <button type="submit" class="neon-btn-primary px-4 py-2 text-sm disabled:opacity-60" :disabled="submitLoading">
                                {{ submitLoading ? 'Saving…' : (editing ? 'Update' : 'Add') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <DecisionModal
            :open="showDeleteModal"
            title="Remove contestant?"
            :message="deleteTarget ? 'Remove ' + deleteTarget.name + '? This cannot be undone.' : ''"
            confirm-label="Remove"
            cancel-label="Cancel"
            variant="danger"
            :loading="deleteLoading"
            @confirm="confirmDelete"
            @cancel="closeDelete"
        />
    </AppLayout>
</template>
