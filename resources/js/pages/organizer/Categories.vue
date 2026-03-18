<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { onMounted, ref, watch } from 'vue';
import { apiHeaders } from '@/lib/api';
import AppLayout from '@/layouts/AppLayout.vue';
import DecisionModal from '@/components/ui/DecisionModal.vue';
import { useToast } from '@/composables/useToast';
import type { BreadcrumbItem } from '@/types';

const toast = useToast();

type Category = {
    id: number;
    event_id: number;
    name: string;
    weight: number;
    description: string | null;
    sort_order: number;
    criteria_count: number;
};

type Props = { event: { id: number; name: string } | null };

const props = defineProps<Props>();

const categories = ref<Category[]>([]);
const loading = ref(true);
const totalWeight = ref(0);

const showModal = ref(false);
const editing = ref<Category | null>(null);
const form = ref({ name: '', weight: 40, description: '' });
const submitLoading = ref(false);
const error = ref('');

const deleteTarget = ref<Category | null>(null);
const showDeleteModal = ref(false);
const deleteLoading = ref(false);
const deliverLoading = ref(false);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Organizer', href: '/organizer/dashboard' },
    { title: 'Scoring setup', href: '/organizer/categories' },
];

async function fetchCategories() {
    if (!props.event) return;
    loading.value = true;
    try {
        const r = await fetch(`/api/v1/organizer/categories?event_id=${props.event.id}`, {
            credentials: 'include',
            headers: { Accept: 'application/json' },
        });
        if (!r.ok) return;
        const json = await r.json();
        categories.value = (json.data ?? []) as Category[];
        totalWeight.value = categories.value.reduce((s, c) => s + Number(c.weight), 0);
    } finally {
        loading.value = false;
    }
}

async function deliverToJudges() {
    if (!props.event) return;

    if (totalWeight.value !== 100) {
        toast.error('Total weight must be exactly 100% before delivering to judges.');
        return;
    }

    if (!window.confirm('Submit this scoring system for judges? Categories and weights should be final.')) {
        return;
    }

    deliverLoading.value = true;
    try {
        const r = await fetch(`/api/v1/organizer/events/${props.event.id}/open-scoring`, {
            method: 'POST',
            credentials: 'include',
            headers: apiHeaders({ method: 'POST', contentType: false }),
        });
        const json = await r.json().catch(() => ({}));
        if (r.ok) {
            toast.success('Scoring system submitted to admins.');
        } else {
            toast.error(json.message || 'Could not submit scoring system.');
        }
    } finally {
        deliverLoading.value = false;
    }
}

function openCreate() {
    editing.value = null;
    form.value = { name: '', weight: 40, description: '' };
    error.value = '';
    showModal.value = true;
}

function openEdit(c: Category) {
    editing.value = c;
    form.value = { name: c.name, weight: Number(c.weight), description: c.description ?? '' };
    error.value = '';
    showModal.value = true;
}

async function save() {
    if (!props.event) return;
    submitLoading.value = true;
    error.value = '';
    try {
        const url = editing.value
            ? `/api/v1/organizer/categories/${editing.value.id}`
            : '/api/v1/organizer/categories';
        const body = editing.value
            ? { name: form.value.name, weight: form.value.weight, description: form.value.description || null }
            : { event_id: props.event.id, name: form.value.name, weight: form.value.weight, description: form.value.description || null };
        const r = await fetch(url, {
            method: editing.value ? 'PUT' : 'POST',
            credentials: 'include',
            headers: apiHeaders({ method: editing.value ? 'PUT' : 'POST' }),
            body: JSON.stringify(body),
        });
        const json = await r.json();
        if (!r.ok) {
            error.value = json.message || 'Request failed';
            toast.error(error.value);
            return;
        }
        showModal.value = false;
        await fetchCategories();
        toast.success(editing.value ? 'Category updated.' : 'Category added.');
    } finally {
        submitLoading.value = false;
    }
}

function openDelete(c: Category) {
    deleteTarget.value = c;
    showDeleteModal.value = true;
}

function closeDelete() {
    deleteTarget.value = null;
    showDeleteModal.value = false;
}

async function confirmDelete() {
    if (!deleteTarget.value) return;
    deleteLoading.value = true;
    try {
        const r = await fetch(`/api/v1/organizer/categories/${deleteTarget.value.id}`, {
            method: 'DELETE',
            credentials: 'include',
            headers: apiHeaders({ method: 'DELETE', contentType: false }),
        });
        if (r.ok) {
            await fetchCategories();
            toast.success('Category removed.');
            closeDelete();
        } else {
            const json = await r.json();
            toast.error(json.message || 'Cannot delete category.');
        }
    } finally {
        deleteLoading.value = false;
    }
}

watch(() => props.event?.id, (id) => { if (id) fetchCategories(); }, { immediate: true });
onMounted(() => { if (props.event) fetchCategories(); });
</script>

<template>
    <Head title="Scoring setup - Organizer" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4">
            <div class="flex flex-col gap-1">
                <h1 class="text-xl font-semibold text-white">Scoring categories</h1>
                <p class="text-sm text-slate-400">
                    Customize the scoring system judges use. Add categories (e.g. Talent, Q&amp;A) and set their weight. Total must be 100%.
                </p>
            </div>
            <div v-if="event" class="flex flex-wrap items-center justify-between gap-3">
                <p class="text-sm text-slate-400">
                    Total weight:
                    <span :class="totalWeight === 100 ? 'text-[#38F298] font-medium' : 'text-amber-400'">{{ totalWeight }}%</span>
                    / 100%
                </p>
                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        class="rounded-xl bg-slate-700 px-4 py-2 text-sm font-semibold text-slate-100 shadow-[0_0_8px_rgba(15,23,42,0.4)] transition hover:bg-slate-600 disabled:opacity-60"
                        :disabled="deliverLoading || categories.length === 0 || totalWeight !== 100"
                        @click="deliverToJudges"
                    >
                        {{ deliverLoading ? 'Submitting…' : 'Submit scoring to admins' }}
                    </button>
                    <button
                        type="button"
                        class="rounded-xl bg-[#F23892] px-4 py-2 text-sm font-semibold text-white shadow-[0_0_12px_rgba(242,56,146,0.4)] transition hover:bg-[#d0206e]"
                        @click="openCreate"
                    >
                        Add category
                    </button>
                </div>
            </div>
            <p v-if="!event" class="text-slate-400">No event selected.</p>
            <div v-else-if="loading" class="rounded-2xl border border-slate-700 bg-slate-900/80 p-8 text-center text-slate-400">
                Loading…
            </div>
            <div v-else class="grid gap-4 md:grid-cols-2">
                <div
                    v-for="c in categories"
                    :key="c.id"
                    class="rounded-2xl border border-slate-700 bg-slate-900/80 p-5"
                >
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0 flex-1">
                            <h2 class="font-medium text-white">{{ c.name }}</h2>
                            <p class="mt-1 text-xs text-slate-400">{{ c.criteria_count }} criteria</p>
                        </div>
                        <span class="shrink-0 rounded-full bg-[#BCD1FF]/20 px-3 py-1 text-sm text-[#BCD1FF]">{{ c.weight }}%</span>
                    </div>
                    <div class="mt-2 h-2 w-full overflow-hidden rounded-full bg-slate-700">
                        <div class="h-full rounded-full bg-[#38F298]" :style="{ width: Math.min(100, c.weight) + '%' }" />
                    </div>
                    <div class="mt-3 flex gap-2">
                        <button type="button" class="text-sm font-medium text-[#BCD1FF] hover:underline" @click="openEdit(c)">Edit</button>
                        <button type="button" class="text-sm font-medium text-red-400 hover:underline" @click="openDelete(c)">Delete</button>
                    </div>
                </div>
            </div>
            <p v-if="event && !loading && categories.length === 0" class="text-slate-400">No categories yet. Add one to define the scoring structure for judges.</p>
        </div>

        <!-- Category modal -->
        <Teleport to="body">
            <Transition name="modal">
                <div
                    v-if="showModal"
                    class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm"
                    @click.self="showModal = false"
                >
                    <div class="w-full max-w-md rounded-3xl border border-slate-700 bg-slate-900 p-6 shadow-2xl">
                        <h2 class="mb-4 text-lg font-semibold text-white">{{ editing ? 'Edit category' : 'Add category' }}</h2>
                        <p v-if="error" class="mb-3 rounded-xl bg-red-500/20 px-3 py-2 text-sm text-red-300">{{ error }}</p>
                        <form class="space-y-4" @submit.prevent="save">
                            <div>
                                <label class="mb-1.5 block text-xs font-medium text-slate-400">Name</label>
                                <input v-model="form.name" type="text" required class="w-full rounded-xl border border-slate-600 bg-slate-800 px-4 py-2.5 text-white focus:border-[#F23892] focus:outline-none focus:ring-2 focus:ring-[#F23892]/30" placeholder="e.g. Talent Portion" />
                            </div>
                            <div>
                                <label class="mb-1.5 block text-xs font-medium text-slate-400">Weight (%)</label>
                                <input v-model.number="form.weight" type="number" min="0" max="100" step="0.01" required class="w-full rounded-xl border border-slate-600 bg-slate-800 px-4 py-2.5 text-white focus:border-[#F23892] focus:outline-none focus:ring-2 focus:ring-[#F23892]/30" />
                            </div>
                            <div>
                                <label class="mb-1.5 block text-xs font-medium text-slate-400">Description (optional)</label>
                                <textarea v-model="form.description" rows="2" class="w-full rounded-xl border border-slate-600 bg-slate-800 px-4 py-2.5 text-white focus:border-[#F23892] focus:outline-none focus:ring-2 focus:ring-[#F23892]/30" placeholder="Brief description" />
                            </div>
                            <div class="flex justify-end gap-3 pt-2">
                                <button type="button" class="rounded-xl border border-slate-600 bg-slate-800 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-slate-700" @click="showModal = false">Cancel</button>
                                <button type="submit" class="rounded-xl bg-[#F23892] px-5 py-2.5 text-sm font-semibold text-white disabled:opacity-60" :disabled="submitLoading">
                                    {{ submitLoading ? 'Saving…' : (editing ? 'Update' : 'Add') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <DecisionModal
            :open="showDeleteModal"
            title="Delete category?"
            :message="deleteTarget ? 'Remove “' + deleteTarget.name + '”? Criteria under it will be removed. This cannot be undone if no scores exist yet.' : ''"
            confirm-label="Delete"
            cancel-label="Cancel"
            variant="danger"
            :loading="deleteLoading"
            @confirm="confirmDelete"
            @cancel="closeDelete"
        />
    </AppLayout>
</template>

<style scoped>
.modal-enter-active,
.modal-leave-active { transition: opacity 0.2s ease; }
.modal-enter-from,
.modal-leave-to { opacity: 0; }
</style>
