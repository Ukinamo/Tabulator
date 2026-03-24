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
                <h1 class="font-headline text-xl font-semibold text-[#0e193d]">Scoring criteria</h1>
                <p class="text-sm text-[#594048]">
                    Define the criteria judges score within each category (e.g. Stage Presence, max 30 pts). Judges will see these on their scoresheet.
                </p>
            </div>
            <div v-if="event" class="flex flex-wrap items-center gap-3">
                <label class="text-sm text-[#594048]">Category:</label>
                <select
                    v-model="selectedCategoryId"
                    class="ig-input rounded-full border-[#e0d8e8] bg-white px-4 py-2 text-[#0e193d] focus:border-[#b40066] focus:ring-[#b40066]/25"
                >
                    <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }} ({{ cat.weight }}%)</option>
                </select>
                <button
                    v-if="selectedCategoryId"
                    type="button"
                    class="neon-btn-primary px-4 py-2 text-sm"
                    @click="openCreate"
                >
                    Add criterion
                </button>
            </div>
            <p v-if="!event" class="text-[#594048]">No event selected.</p>
            <div v-else-if="loading" class="neon-card border border-[#e8e6f5] p-8 text-center text-[#594048]">
                Loading…
            </div>
            <div v-else class="neon-card overflow-hidden border border-[#e8e6f5]">
                <table class="min-w-full text-left text-sm text-[#0e193d]">
                    <thead class="border-b border-[#e8e6f5] bg-[#f3f2ff] text-xs uppercase tracking-wide text-[#594048]">
                        <tr>
                            <th class="px-5 py-4">Name</th>
                            <th class="px-5 py-4">Max score</th>
                            <th class="px-5 py-4 w-24">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="c in criteria" :key="c.id" class="border-b border-[#ebedff] transition hover:bg-[#ebedff]/60">
                            <td class="px-5 py-4 font-medium">{{ c.name }}</td>
                            <td class="px-5 py-4">
                                <span class="rounded-full bg-[#b40066]/12 px-2.5 py-1 text-xs font-medium text-[#b40066]">{{ c.max_score }} pts</span>
                            </td>
                            <td class="px-5 py-4">
                                <button type="button" class="mr-2 text-[#4a5e86] hover:underline" @click="openEdit(c)">Edit</button>
                                <button type="button" class="text-red-600 hover:underline" @click="openDelete(c)">Delete</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <p v-if="criteria.length === 0" class="p-8 text-center text-[#594048]">
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
                    <div class="neon-glass-panel w-full max-w-md rounded-3xl border border-border p-6 shadow-2xl">
                        <h2 class="mb-4 font-headline text-lg font-semibold text-foreground">{{ editing ? 'Edit criterion' : 'Add criterion' }}</h2>
                        <p v-if="error" class="mb-3 rounded-xl bg-red-500/15 px-3 py-2 text-sm text-red-700 dark:text-red-300">{{ error }}</p>
                        <form class="space-y-4" @submit.prevent="save">
                            <div>
                                <label class="mb-1.5 block text-xs font-medium text-muted-foreground">Name</label>
                                <input v-model="form.name" type="text" required class="ig-input w-full rounded-xl" placeholder="e.g. Stage Presence" />
                            </div>
                            <div>
                                <label class="mb-1.5 block text-xs font-medium text-muted-foreground">Max score (pts)</label>
                                <input v-model.number="form.max_score" type="number" min="0" step="0.01" required class="ig-input w-full rounded-xl" />
                            </div>
                            <div>
                                <label class="mb-1.5 block text-xs font-medium text-muted-foreground">Description (optional)</label>
                                <textarea v-model="form.description" rows="2" class="ig-input w-full rounded-xl" placeholder="Brief description" />
                            </div>
                            <div class="flex justify-end gap-3 pt-2">
                                <button type="button" class="rounded-full border border-border bg-card px-4 py-2.5 text-sm font-medium text-foreground transition hover:bg-muted" @click="showModal = false">Cancel</button>
                                <button type="submit" class="neon-btn-primary px-5 py-2.5 text-sm disabled:opacity-60" :disabled="submitLoading">
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
