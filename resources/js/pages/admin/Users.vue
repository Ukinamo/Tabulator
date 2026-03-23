<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import { apiHeaders } from '@/lib/api';
import AppLayout from '@/layouts/AppLayout.vue';
import DecisionModal from '@/components/ui/DecisionModal.vue';
import { useToast } from '@/composables/useToast';
import type { BreadcrumbItem } from '@/types';

type UserRow = {
    id: number;
    name: string;
    email: string;
    role: string;
    is_active: boolean;
};

type Props = {
    event: { id: number; name: string } | null;
};

defineProps<Props>();

const toast = useToast();
const users = ref<UserRow[]>([]);
const loading = ref(true);
const showModal = ref(false);
const editing = ref<UserRow | null>(null);
const form = ref({ name: '', email: '', password: '', password_confirmation: '', role: 'admin', is_active: true });
const submitLoading = ref(false);
const error = ref('');

const deleteTarget = ref<UserRow | null>(null);
const showDeleteModal = ref(false);
const deleteLoading = ref(false);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: '/admin/dashboard' },
    { title: 'Users', href: '/admin/users' },
];

async function fetchUsers() {
    loading.value = true;
    try {
        const r = await fetch('/api/v1/admin/users', { credentials: 'include', headers: { Accept: 'application/json' } });
        if (!r.ok) return;
        const json = await r.json();
        users.value = (json.data ?? []) as UserRow[];
    } finally {
        loading.value = false;
    }
}

function openCreate() {
    editing.value = null;
    form.value = { name: '', email: '', password: '', password_confirmation: '', role: 'admin', is_active: true };
    error.value = '';
    showModal.value = true;
}

function openEdit(u: UserRow) {
    editing.value = u;
    form.value = { name: u.name, email: u.email, password: '', password_confirmation: '', role: u.role, is_active: u.is_active };
    error.value = '';
    showModal.value = true;
}

async function save() {
    submitLoading.value = true;
    error.value = '';
    try {
        const url = editing.value
            ? `/api/v1/admin/users/${editing.value.id}`
            : '/api/v1/admin/users';
        const body = editing.value
            ? { name: form.value.name, email: form.value.email, is_active: form.value.is_active }
            : { ...form.value };
        const r = await fetch(url, {
            method: editing.value ? 'PUT' : 'POST',
            credentials: 'include',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
            body: JSON.stringify(body),
        });
        const json = await r.json();
        if (!r.ok) {
            error.value = json.message || (json.errors ? Object.values(json.errors).flat().join(' ') : 'Request failed');
            toast.error(error.value);
            return;
        }
        showModal.value = false;
        await fetchUsers();
        toast.success(editing.value ? 'User updated.' : 'User created.');
    } finally {
        submitLoading.value = false;
    }
}

function openDelete(u: UserRow) {
    deleteTarget.value = u;
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
        const r = await fetch(`/api/v1/admin/users/${deleteTarget.value.id}`, { method: 'DELETE', credentials: 'include', headers: apiHeaders({ method: 'DELETE', contentType: false }) });
        if (r.ok) {
            await fetchUsers();
            toast.success('User removed.');
            closeDelete();
        } else {
            const json = await r.json();
            toast.error(json.message || 'Could not delete user.');
        }
    } finally {
        deleteLoading.value = false;
    }
}

onMounted(fetchUsers);
</script>

<template>
    <Head title="Users - Admin" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4">
            <div class="flex items-center justify-between">
                <h1 class="font-headline text-xl font-semibold text-[#0e193d]">Users</h1>
                <button
                    type="button"
                    class="ig-btn-primary px-5 py-2.5 text-sm text-white"
                    @click="openCreate"
                >
                    Create User
                </button>
            </div>

            <div v-if="loading" class="neon-card flex items-center justify-center gap-2 border border-[#e8e6f5] p-10 text-[#594048]">
                <span class="inline-block h-5 w-5 animate-spin rounded-full border-2 border-[#b40066] border-t-transparent" />
                <span>Loading…</span>
            </div>

            <div v-else class="neon-card overflow-hidden border border-[#e8e6f5]">
                <table class="min-w-full text-left text-sm text-[#0e193d]">
                    <thead class="border-b border-[#e8e6f5] bg-[#f3f2ff] text-xs uppercase tracking-wide text-[#594048]">
                        <tr>
                            <th class="px-5 py-4">Name</th>
                            <th class="px-5 py-4">Email</th>
                            <th class="px-5 py-4">Role</th>
                            <th class="px-5 py-4">Status</th>
                            <th class="px-5 py-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="u in users" :key="u.id" class="border-b border-[#ebedff] transition hover:bg-[#ebedff]/60">
                            <td class="px-5 py-4 font-medium">{{ u.name }}</td>
                            <td class="px-5 py-4 text-[#594048]">{{ u.email }}</td>
                            <td class="px-5 py-4">
                                <span class="rounded-full bg-[#4a5e86]/12 px-2.5 py-1 text-xs font-medium text-[#4a5e86]">{{ u.role }}</span>
                            </td>
                            <td class="px-5 py-4">
                                <span :class="u.is_active ? 'font-medium text-[#006a3d]' : 'text-[#594048]'">{{ u.is_active ? 'Active' : 'Inactive' }}</span>
                            </td>
                            <td class="px-5 py-4">
                                <button type="button" class="mr-3 text-sm font-medium text-[#b40066] hover:underline" @click="openEdit(u)">Edit</button>
                                <button type="button" class="text-sm font-medium text-red-600 hover:underline" @click="openDelete(u)">Delete</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <p v-if="!loading && users.length === 0" class="p-8 text-center text-[#594048]">No users yet.</p>
            </div>
        </div>

        <!-- Create/Edit Modal (Instagram-style) -->
        <Teleport to="body">
            <Transition name="modal">
                <div
                    v-if="showModal"
                    class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm"
                    @click.self="showModal = false"
                >
                    <div class="neon-glass-panel w-full max-w-md rounded-3xl border border-[#e8e6f5] p-6 shadow-2xl">
                        <h2 class="mb-5 font-headline text-lg font-semibold text-[#0e193d]">{{ editing ? 'Edit User' : 'Create User' }}</h2>
                        <p v-if="error" class="mb-3 rounded-xl bg-red-500/15 px-3 py-2 text-sm text-red-700">{{ error }}</p>
                        <form class="space-y-4" @submit.prevent="save">
                            <div>
                                <label class="mb-1.5 block text-xs font-medium text-[#594048]">Name</label>
                                <input v-model="form.name" type="text" required class="ig-input w-full" />
                            </div>
                            <div>
                                <label class="mb-1.5 block text-xs font-medium text-[#594048]">Email</label>
                                <input v-model="form.email" type="email" required :readonly="!!editing" class="ig-input w-full" />
                            </div>
                            <div v-if="!editing">
                                <label class="mb-1.5 block text-xs font-medium text-[#594048]">Password</label>
                                <input v-model="form.password" type="password" class="ig-input w-full" />
                            </div>
                            <div v-if="!editing">
                                <label class="mb-1.5 block text-xs font-medium text-[#594048]">Confirm Password</label>
                                <input v-model="form.password_confirmation" type="password" class="ig-input w-full" />
                            </div>
                            <div v-if="!editing">
                                <label class="mb-1.5 block text-xs font-medium text-[#594048]">Role</label>
                                <select v-model="form.role" class="ig-input w-full">
                                    <option value="admin">Judge</option>
                                    <option value="mc">MC</option>
                                    <option value="organizer">Organizer</option>
                                </select>
                            </div>
                            <div class="flex items-center gap-2">
                                <input v-model="form.is_active" type="checkbox" class="h-4 w-4 rounded border-[#e0d8e8] text-[#b40066] focus:ring-[#b40066]" />
                                <label class="text-sm text-[#0e193d]">Active</label>
                            </div>
                            <div class="flex justify-end gap-3 pt-2">
                                <button type="button" class="rounded-full border border-[#e0d8e8] bg-white px-4 py-2.5 text-sm font-medium text-[#0e193d] transition hover:bg-[#f3f2ff]" @click="showModal = false">Cancel</button>
                                <button type="submit" class="ig-btn-primary px-5 py-2.5 text-sm text-white disabled:opacity-60" :disabled="submitLoading">
                                    <span v-if="submitLoading" class="inline-flex items-center gap-2">
                                        <span class="inline-block h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent" />
                                        {{ editing ? 'Updating…' : 'Creating…' }}
                                    </span>
                                    <span v-else>{{ editing ? 'Update' : 'Create' }}</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <DecisionModal
            :open="showDeleteModal"
            title="Delete user?"
            :message="deleteTarget ? `Remove ${deleteTarget.email} from the system? This cannot be undone.` : ''"
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
.modal-leave-active {
    transition: opacity 0.2s ease;
}
.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}
</style>
