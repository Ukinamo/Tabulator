<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { onMounted, ref, watch } from 'vue';
import { apiHeaders } from '@/lib/api';
import AppLayout from '@/layouts/AppLayout.vue';
import DecisionModal from '@/components/ui/DecisionModal.vue';
import { useToast } from '@/composables/useToast';
import type { BreadcrumbItem } from '@/types';

const toast = useToast();

type Category = { id: number; name: string; weight: number };
type Criterion = { id: number; category_id: number; name: string; max_score: number; description?: string | null; sort_order?: number };

type Props = { event: { id: number; name: string } | null };

const props = defineProps<Props>();

const categories = ref<Category[]>([]);
const criteria = ref<Criterion[]>([]);
const selectedCategoryId = ref<number | null>(null);
const loading = ref(true);

const showModal = ref(false);
const editing = ref<Criterion | null>(null);
const form = ref({ name: '', max_score: 10, description: '' });
const submitLoading = ref(false);
const error = ref('');

const deleteTarget = ref<Criterion | null>(null);
const showDeleteModal = ref(false);
const deleteLoading = ref(false);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Organizer', href: '/organizer/dashboard' },
    { title: 'Criteria', href: '/organizer/criteria' },
];

async function fetchCategories() {
    if (!props.event) return;
    const r = await fetch(`/api/v1/organizer/categories?event_id=${props.event.id}`, { credentials: 'include', headers: { Accept: 'application/json' } });
    if (!r.ok) return;
    const json = await r.json();
    categories.value = (json.data ?? []) as Category[];
    if (categories.value.length && !selectedCategoryId.value) selectedCategoryId.value = categories.value[0].id;
}

async function fetchCriteria() {
    if (!selectedCategoryId.value) return;
    loading.value = true;
    try {
        const r = await fetch(`/api/v1/organizer/categories/${selectedCategoryId.value}/criteria`, { credentials: 'include', headers: { Accept: 'application/json' } });
        if (!r.ok) return;
        const json = await r.json();
        criteria.value = (json.data ?? []) as Criterion[];
    } finally {
        loading.value = false;
    }
}

function openCreate() {
    editing.value = null;
    form.value = { name: '', max_score: 10, description: '' };
    error.value = '';
    showModal.value = true;
}

function openEdit(c: Criterion) {
    editing.value = c;
    form.value = { name: c.name, max_score: Number(c.max_score), description: (c.description ?? '') };
    error.value = '';
    showModal.value = true;
}

async function save() {
    if (!selectedCategoryId.value) return;
    submitLoading.value = true;
    error.value = '';
    try {
        const url = editing.value
            ? `/api/v1/organizer/categories/${selectedCategoryId.value}/criteria/${editing.value.id}`
            : `/api/v1/organizer/categories/${selectedCategoryId.value}/criteria`;
        const body = editing.value
            ? { name: form.value.name, max_score: form.value.max_score, description: form.value.description || null }
            : { name: form.value.name, max_score: form.value.max_score, description: form.value.description || null };
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
        await fetchCriteria();
        toast.success(editing.value ? 'Criterion updated.' : 'Criterion added.');
    } finally {
        submitLoading.value = false;
    }
}

function openDelete(c: Criterion) {
    deleteTarget.value = c;
    showDeleteModal.value = true;
}

function closeDelete() {
    deleteTarget.value = null;
    showDeleteModal.value = false;
}

async function confirmDelete() {
    if (!selectedCategoryId.value || !deleteTarget.value) return;
    deleteLoading.value = true;
    try {
        const r = await fetch(`/api/v1/organizer/categories/${selectedCategoryId.value}/criteria/${deleteTarget.value.id}`, {
            method: 'DELETE',
            credentials: 'include',
            headers: apiHeaders({ method: 'DELETE', contentType: false }),
        });
        if (r.ok) {
            await fetchCriteria();
            toast.success('Criterion removed.');
            closeDelete();
        } else {
            const json = await r.json();
            toast.error(json.message || 'Cannot delete criterion.');
        }
    } finally {
        deleteLoading.value = false;
    }
}

const selectedCategory = ref<Category | null>(null);
watch(selectedCategoryId, (id) => {
    selectedCategory.value = categories.value.find((c) => c.id === id) ?? null;
}, { immediate: true });

watch(selectedCategoryId, () => fetchCriteria());
watch(() => props.event?.id, (id) => { if (id) fetchCategories(); }, { immediate: true });
onMounted(() => { if (props.event) fetchCategories(); });
watch([categories, selectedCategoryId], () => { if (selectedCategoryId.value) fetchCriteria(); }, { immediate: true });
</script>

<template>
    <Head title="Criteria - Organizer" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4">
            <div class="flex flex-col gap-1">
                <h1 class="text-xl font-semibold text-white">Scoring criteria</h1>
                <p class="text-sm text-slate-400">
                    Define the criteria judges score within each category (e.g. Stage Presence, max 30 pts). Judges will see these on their scoresheet.
                </p>
            </div>
            <div v-if="event" class="flex flex-wrap items-center gap-3">
                <label class="text-sm text-slate-400">Category:</label>
                <select
                    v-model="selectedCategoryId"
                    class="rounded-xl border border-slate-600 bg-slate-800 px-4 py-2 text-white focus:border-[#F23892] focus:outline-none focus:ring-2 focus:ring-[#F23892]/30"
                >
                    <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }} ({{ cat.weight }}%)</option>
                </select>
                <button
                    v-if="selectedCategoryId"
                    type="button"
                    class="rounded-xl bg-[#F23892] px-4 py-2 text-sm font-semibold text-white shadow-[0_0_12px_rgba(242,56,146,0.4)] transition hover:bg-[#d0206e]"
                    @click="openCreate"
                >
                    Add criterion
                </button>
            </div>
            <p v-if="!event" class="text-slate-400">No event selected.</p>
            <div v-else-if="loading" class="rounded-2xl border border-slate-700 bg-slate-900/80 p-8 text-center text-slate-400">
                Loading…
            </div>
            <div v-else class="overflow-hidden rounded-2xl border border-slate-700 bg-slate-900/80">
                <table class="min-w-full text-left text-sm text-slate-200">
                    <thead class="border-b border-slate-700 bg-slate-800/50 text-xs uppercase tracking-wide text-slate-400">
                        <tr>
                            <th class="px-5 py-4">Name</th>
                            <th class="px-5 py-4">Max score</th>
                            <th class="px-5 py-4 w-24">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="c in criteria" :key="c.id" class="border-b border-slate-800/70 transition hover:bg-slate-800/30">
                            <td class="px-5 py-4 font-medium text-white">{{ c.name }}</td>
                            <td class="px-5 py-4">
                                <span class="rounded-full bg-[#F23892]/20 px-2.5 py-1 text-xs font-medium text-[#F23892]">{{ c.max_score }} pts</span>
                            </td>
                            <td class="px-5 py-4">
                                <button type="button" class="mr-2 text-[#BCD1FF] hover:underline" @click="openEdit(c)">Edit</button>
                                <button type="button" class="text-red-400 hover:underline" @click="openDelete(c)">Delete</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <p v-if="criteria.length === 0" class="p-8 text-center text-slate-400">
                    No criteria in {{ selectedCategory?.name ?? 'this category' }}. Add criteria so judges can score them.
                </p>
            </div>
        </div>

        <!-- Criterion modal -->
        <Teleport to="body">
            <Transition name="modal">
                <div
                    v-if="showModal"
                    class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm"
                    @click.self="showModal = false"
                >
                    <div class="w-full max-w-md rounded-3xl border border-slate-700 bg-slate-900 p-6 shadow-2xl">
                        <h2 class="mb-4 text-lg font-semibold text-white">{{ editing ? 'Edit criterion' : 'Add criterion' }}</h2>
                        <p v-if="error" class="mb-3 rounded-xl bg-red-500/20 px-3 py-2 text-sm text-red-300">{{ error }}</p>
                        <form class="space-y-4" @submit.prevent="save">
                            <div>
                                <label class="mb-1.5 block text-xs font-medium text-slate-400">Name</label>
                                <input v-model="form.name" type="text" required class="w-full rounded-xl border border-slate-600 bg-slate-800 px-4 py-2.5 text-white focus:border-[#F23892] focus:outline-none focus:ring-2 focus:ring-[#F23892]/30" placeholder="e.g. Stage Presence" />
                            </div>
                            <div>
                                <label class="mb-1.5 block text-xs font-medium text-slate-400">Max score (pts)</label>
                                <input v-model.number="form.max_score" type="number" min="0" step="0.01" required class="w-full rounded-xl border border-slate-600 bg-slate-800 px-4 py-2.5 text-white focus:border-[#F23892] focus:outline-none focus:ring-2 focus:ring-[#F23892]/30" />
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
            title="Delete criterion?"
            :message="deleteTarget ? 'Remove “' + deleteTarget.name + '”? This cannot be undone if scores have been entered.' : ''"
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
