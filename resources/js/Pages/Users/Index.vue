<script setup lang="ts">
import { ref, watch } from 'vue';
import { router, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PageHeader from '@/Components/Shared/PageHeader.vue';
import StatusBadge from '@/Components/Shared/StatusBadge.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import Tag from 'primevue/tag';
import Password from 'primevue/password';
import type { PaginatedData, User, Agency, Role } from '@/Types/models';
import { useConfirm } from '@/Composables/useConfirm';
import { usePermissions } from '@/Composables/usePermissions';
import { useDebounceFn } from '@vueuse/core';

defineOptions({ layout: AdminLayout });

interface Props {
    users: PaginatedData<User>;
    filters: Record<string, string>;
    userTypes: { value: string; label: string }[];
    statuses: { value: string; label: string }[];
    roles: Role[];
    agencies: Agency[];
}

const props = defineProps<Props>();

const { can } = usePermissions();
const { confirmDelete } = useConfirm();

// Filters
const search = ref(props.filters.search ?? '');
const typeFilter = ref<string | null>(props.filters.user_type ?? null);
const statusFilter = ref<string | null>(props.filters.status ?? null);

// Modal
const showFormModal = ref(false);
const editingUser = ref<User | null>(null);

const form = useForm({
    email: '',
    password: '',
    first_name: '',
    last_name: '',
    phone: '',
    user_type: 'end_user',
    agency_id: null as number | null,
    preferred_language: 'en',
    status: 'active',
    role: '',
});

const languageOptions = [
    { label: 'English', value: 'en' },
    { label: 'Français', value: 'fr' },
    { label: 'Deutsch', value: 'de' },
    { label: 'Italiano', value: 'it' },
];

function reload(extra: Record<string, unknown> = {}) {
    router.get(route('admin.users.index'), {
        search: search.value || undefined,
        user_type: typeFilter.value || undefined,
        status: statusFilter.value || undefined,
        ...extra,
    }, { preserveState: true, preserveScroll: true });
}

const debouncedSearch = useDebounceFn(() => reload(), 400);

watch(search, () => debouncedSearch());
watch([typeFilter, statusFilter], () => reload());

function onPage(event: { page: number; rows: number }) {
    reload({ page: event.page + 1, limit: event.rows });
}

function clearFilters() {
    search.value = '';
    typeFilter.value = null;
    statusFilter.value = null;
    reload();
}

function openCreateModal() {
    editingUser.value = null;
    form.reset();
    form.clearErrors();
    showFormModal.value = true;
}

function openEditModal(user: User) {
    editingUser.value = user;
    form.email = user.email;
    form.password = '';
    form.first_name = user.first_name;
    form.last_name = user.last_name;
    form.phone = user.phone ?? '';
    form.user_type = user.user_type;
    form.agency_id = user.agency_id;
    form.preferred_language = user.preferred_language;
    form.status = user.status;
    form.role = user.roles?.[0]?.name ?? '';
    form.clearErrors();
    showFormModal.value = true;
}

function submitForm() {
    if (editingUser.value) {
        form.put(route('admin.users.update', editingUser.value.id), {
            onSuccess: () => { showFormModal.value = false; },
        });
    } else {
        form.post(route('admin.users.store'), {
            onSuccess: () => { showFormModal.value = false; },
        });
    }
}

function deleteUser(id: number) {
    confirmDelete(() => {
        router.delete(route('admin.users.destroy', id));
    });
}

function formatDate(date: string | null) {
    if (!date) return '–';
    return new Date(date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}
</script>

<template>
    <PageHeader title="Users" description="Manage platform users">
        <template #actions>
            <Button v-if="can('users:create')" label="Add User" icon="pi pi-plus" @click="openCreateModal" />
        </template>
    </PageHeader>

    <!-- Filters -->
    <div class="mb-4 rounded-lg border border-gray-200 bg-white p-4">
        <div class="flex flex-wrap items-center gap-3">
            <span class="p-input-icon-left">
                <i class="pi pi-search" />
                <InputText v-model="search" placeholder="Search users..." class="w-64" />
            </span>

            <Select
                v-model="typeFilter"
                :options="userTypes"
                optionLabel="label"
                optionValue="value"
                placeholder="All Types"
                showClear
                class="w-48"
            />

            <Select
                v-model="statusFilter"
                :options="statuses"
                optionLabel="label"
                optionValue="value"
                placeholder="All Statuses"
                showClear
                class="w-40"
            />

            <Button label="Clear" icon="pi pi-filter-slash" severity="secondary" text @click="clearFilters" />
        </div>
    </div>

    <!-- DataTable -->
    <div class="rounded-lg border border-gray-200 bg-white">
        <DataTable
            :value="users.data"
            :lazy="true"
            :paginator="true"
            :rows="users.meta.per_page"
            :totalRecords="users.meta.total"
            :rowsPerPageOptions="[10, 15, 25, 50]"
            paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
            @page="onPage"
            stripedRows
            responsiveLayout="scroll"
            data-testid="users-table"
        >
            <Column field="full_name" header="Name" style="min-width: 180px">
                <template #body="{ data }">
                    <Link :href="route('admin.users.show', data.id)" class="font-medium text-blue-600 hover:underline">
                        {{ data.full_name }}
                    </Link>
                </template>
            </Column>

            <Column field="email" header="Email" style="min-width: 200px" />

            <Column field="user_type" header="Type" style="min-width: 130px">
                <template #body="{ data }">
                    <Tag :value="data.user_type.replace(/_/g, ' ')" severity="info" />
                </template>
            </Column>

            <Column field="status" header="Status" style="min-width: 110px">
                <template #body="{ data }">
                    <StatusBadge :status="data.status" type="account" />
                </template>
            </Column>

            <Column field="roles" header="Role" style="min-width: 120px">
                <template #body="{ data }">
                    {{ data.roles?.map((r: any) => r.name).join(', ') || '–' }}
                </template>
            </Column>

            <Column field="agency" header="Agency" style="min-width: 140px">
                <template #body="{ data }">
                    {{ data.agency?.name ?? '–' }}
                </template>
            </Column>

            <Column field="created_at" header="Joined" style="min-width: 110px">
                <template #body="{ data }">
                    {{ formatDate(data.created_at) }}
                </template>
            </Column>

            <Column header="Actions" style="min-width: 120px" :exportable="false">
                <template #body="{ data }">
                    <div class="flex items-center gap-1">
                        <Link :href="route('admin.users.show', data.id)">
                            <Button icon="pi pi-eye" severity="info" text rounded size="small" />
                        </Link>
                        <Button
                            v-if="can('users:manage')"
                            icon="pi pi-pencil"
                            severity="warn"
                            text
                            rounded
                            size="small"
                            @click="openEditModal(data)"
                        />
                        <Button
                            v-if="can('users:delete')"
                            icon="pi pi-trash"
                            severity="danger"
                            text
                            rounded
                            size="small"
                            @click="deleteUser(data.id)"
                        />
                    </div>
                </template>
            </Column>

            <template #empty>
                <div class="py-8 text-center text-gray-500">No users found.</div>
            </template>
        </DataTable>
    </div>

    <!-- Create / Edit Modal -->
    <Dialog
        v-model:visible="showFormModal"
        :header="editingUser ? 'Edit User' : 'Create User'"
        modal
        :style="{ width: '600px' }"
        data-testid="user-form-modal"
    >
        <form @submit.prevent="submitForm" class="flex flex-col gap-4">
            <div v-if="!editingUser">
                <label for="email" class="mb-1 block text-sm font-medium">Email *</label>
                <InputText id="email" v-model="form.email" class="w-full" :invalid="!!form.errors.email" />
                <small v-if="form.errors.email" class="text-red-500">{{ form.errors.email }}</small>
            </div>

            <div v-if="!editingUser">
                <label for="password" class="mb-1 block text-sm font-medium">Password *</label>
                <Password id="password" v-model="form.password" class="w-full" toggleMask :feedback="false" :invalid="!!form.errors.password" />
                <small v-if="form.errors.password" class="text-red-500">{{ form.errors.password }}</small>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="first_name" class="mb-1 block text-sm font-medium">First Name *</label>
                    <InputText id="first_name" v-model="form.first_name" class="w-full" :invalid="!!form.errors.first_name" />
                    <small v-if="form.errors.first_name" class="text-red-500">{{ form.errors.first_name }}</small>
                </div>
                <div>
                    <label for="last_name" class="mb-1 block text-sm font-medium">Last Name *</label>
                    <InputText id="last_name" v-model="form.last_name" class="w-full" :invalid="!!form.errors.last_name" />
                    <small v-if="form.errors.last_name" class="text-red-500">{{ form.errors.last_name }}</small>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="phone" class="mb-1 block text-sm font-medium">Phone</label>
                    <InputText id="phone" v-model="form.phone" class="w-full" />
                </div>
                <div>
                    <label for="preferred_language" class="mb-1 block text-sm font-medium">Language</label>
                    <Select id="preferred_language" v-model="form.preferred_language" :options="languageOptions" optionLabel="label" optionValue="value" class="w-full" />
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="user_type" class="mb-1 block text-sm font-medium">User Type *</label>
                    <Select id="user_type" v-model="form.user_type" :options="userTypes" optionLabel="label" optionValue="value" class="w-full" :invalid="!!form.errors.user_type" />
                    <small v-if="form.errors.user_type" class="text-red-500">{{ form.errors.user_type }}</small>
                </div>
                <div>
                    <label for="role" class="mb-1 block text-sm font-medium">Role</label>
                    <Select id="role" v-model="form.role" :options="roles" optionLabel="name" optionValue="name" placeholder="Select role..." showClear class="w-full" />
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="agency_id" class="mb-1 block text-sm font-medium">Agency</label>
                    <Select id="agency_id" v-model="form.agency_id" :options="agencies" optionLabel="name" optionValue="id" placeholder="No agency" showClear class="w-full" />
                </div>
                <div>
                    <label for="status" class="mb-1 block text-sm font-medium">Status</label>
                    <Select id="status" v-model="form.status" :options="statuses" optionLabel="label" optionValue="value" class="w-full" />
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <Button label="Cancel" severity="secondary" text @click="showFormModal = false" />
                <Button
                    type="submit"
                    :label="editingUser ? 'Update' : 'Create'"
                    :loading="form.processing"
                    data-testid="save-button"
                />
            </div>
        </form>
    </Dialog>
</template>
