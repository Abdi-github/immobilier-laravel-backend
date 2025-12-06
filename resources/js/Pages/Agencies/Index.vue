<script setup lang="ts">
import { ref, watch } from 'vue';
import { router, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PageHeader from '@/Components/Shared/PageHeader.vue';
import StatusBadge from '@/Components/Shared/StatusBadge.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Select from 'primevue/select';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import Tag from 'primevue/tag';
import Textarea from 'primevue/textarea';
import type { PaginatedData, Agency, Canton } from '@/Types/models';
import { useConfirm } from '@/Composables/useConfirm';
import { usePermissions } from '@/Composables/usePermissions';
import { useDebounceFn } from '@vueuse/core';

defineOptions({ layout: AdminLayout });

interface Props {
    agencies: PaginatedData<Agency>;
    filters: Record<string, string>;
    cantons: Canton[];
}

const props = defineProps<Props>();

const { can } = usePermissions();
const { confirmDelete } = useConfirm();

// Filters
const search = ref(props.filters.search ?? '');
const statusFilter = ref<string | null>(props.filters.status ?? null);
const cantonFilter = ref<number | null>(props.filters.canton_id ? Number(props.filters.canton_id) : null);
const verifiedFilter = ref<string | null>(props.filters.is_verified ?? null);

// Modal
const showFormModal = ref(false);
const editingAgency = ref<Agency | null>(null);

const form = useForm({
    name: '',
    email: '',
    phone: '',
    website: '',
    contact_person: '',
    address: '',
    canton_id: null as number | null,
    city_id: null as number | null,
    postal_code: '',
    status: 'active',
});

const statusOptions = [
    { label: 'Active', value: 'active' },
    { label: 'Inactive', value: 'inactive' },
    { label: 'Suspended', value: 'suspended' },
];

const verifiedOptions = [
    { label: 'Verified', value: 'true' },
    { label: 'Unverified', value: 'false' },
];

function reload(extra: Record<string, unknown> = {}) {
    router.get(route('admin.agencies.index'), {
        search: search.value || undefined,
        status: statusFilter.value || undefined,
        canton_id: cantonFilter.value || undefined,
        is_verified: verifiedFilter.value || undefined,
        ...extra,
    }, { preserveState: true, preserveScroll: true });
}

const debouncedSearch = useDebounceFn(() => reload(), 400);

watch(search, () => debouncedSearch());
watch([statusFilter, cantonFilter, verifiedFilter], () => reload());

function onPage(event: { page: number; rows: number }) {
    reload({ page: event.page + 1, limit: event.rows });
}

function clearFilters() {
    search.value = '';
    statusFilter.value = null;
    cantonFilter.value = null;
    verifiedFilter.value = null;
    reload();
}

function openCreateModal() {
    editingAgency.value = null;
    form.reset();
    form.clearErrors();
    showFormModal.value = true;
}

function openEditModal(agency: Agency) {
    editingAgency.value = agency;
    form.name = agency.name;
    form.email = agency.email ?? '';
    form.phone = agency.phone ?? '';
    form.website = agency.website ?? '';
    form.contact_person = agency.contact_person ?? '';
    form.address = agency.address ?? '';
    form.canton_id = agency.canton_id;
    form.city_id = agency.city_id;
    form.postal_code = agency.postal_code ?? '';
    form.status = agency.status;
    form.clearErrors();
    showFormModal.value = true;
}

function submitForm() {
    if (editingAgency.value) {
        form.put(route('admin.agencies.update', editingAgency.value.id), {
            onSuccess: () => { showFormModal.value = false; },
        });
    } else {
        form.post(route('admin.agencies.store'), {
            onSuccess: () => { showFormModal.value = false; },
        });
    }
}

function deleteAgency(id: number) {
    confirmDelete(() => {
        router.delete(route('admin.agencies.destroy', id));
    });
}
</script>

<template>
    <PageHeader title="Agencies" description="Manage real estate agencies">
        <template #actions>
            <Button v-if="can('agencies:create')" label="Add Agency" icon="pi pi-plus" @click="openCreateModal" />
        </template>
    </PageHeader>

    <!-- Filters -->
    <div class="mb-4 rounded-lg border border-gray-200 bg-white p-4">
        <div class="flex flex-wrap items-center gap-3">
            <span class="p-input-icon-left">
                <i class="pi pi-search" />
                <InputText v-model="search" placeholder="Search agencies..." class="w-64" />
            </span>

            <Select
                v-model="statusFilter"
                :options="statusOptions"
                optionLabel="label"
                optionValue="value"
                placeholder="All Statuses"
                showClear
                class="w-44"
            />

            <Select
                v-model="cantonFilter"
                :options="cantons"
                optionLabel="code"
                optionValue="id"
                placeholder="All Cantons"
                showClear
                class="w-40"
            />

            <Select
                v-model="verifiedFilter"
                :options="verifiedOptions"
                optionLabel="label"
                optionValue="value"
                placeholder="All Verification"
                showClear
                class="w-44"
            />

            <Button label="Clear" icon="pi pi-filter-slash" severity="secondary" text @click="clearFilters" />
        </div>
    </div>

    <!-- DataTable -->
    <div class="rounded-lg border border-gray-200 bg-white">
        <DataTable
            :value="agencies.data"
            :lazy="true"
            :paginator="true"
            :rows="agencies.meta.per_page"
            :totalRecords="agencies.meta.total"
            :rowsPerPageOptions="[10, 15, 25, 50]"
            paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
            @page="onPage"
            stripedRows
            responsiveLayout="scroll"
            data-testid="agencies-table"
        >
            <Column field="name" header="Name" style="min-width: 200px">
                <template #body="{ data }">
                    <Link :href="route('admin.agencies.show', data.id)" class="font-medium text-blue-600 hover:underline">
                        {{ data.name }}
                    </Link>
                </template>
            </Column>

            <Column field="email" header="Email" style="min-width: 180px">
                <template #body="{ data }">
                    {{ data.email ?? '–' }}
                </template>
            </Column>

            <Column field="canton" header="Canton" style="min-width: 80px">
                <template #body="{ data }">
                    {{ data.canton?.code ?? '–' }}
                </template>
            </Column>

            <Column field="city" header="City" style="min-width: 120px">
                <template #body="{ data }">
                    {{ data.city?.name ?? '–' }}
                </template>
            </Column>

            <Column field="status" header="Status" style="min-width: 110px">
                <template #body="{ data }">
                    <StatusBadge :status="data.status" type="account" />
                </template>
            </Column>

            <Column field="is_verified" header="Verified" style="min-width: 100px">
                <template #body="{ data }">
                    <Tag :value="data.is_verified ? 'Verified' : 'Unverified'" :severity="data.is_verified ? 'success' : 'warn'" />
                </template>
            </Column>

            <Column field="properties_count" header="Properties" style="min-width: 100px">
                <template #body="{ data }">
                    {{ data.properties_count ?? 0 }}
                </template>
            </Column>

            <Column field="members_count" header="Members" style="min-width: 90px">
                <template #body="{ data }">
                    {{ data.members_count ?? 0 }}
                </template>
            </Column>

            <Column header="Actions" style="min-width: 120px" :exportable="false">
                <template #body="{ data }">
                    <div class="flex items-center gap-1">
                        <Link :href="route('admin.agencies.show', data.id)">
                            <Button icon="pi pi-eye" severity="info" text rounded size="small" />
                        </Link>
                        <Button
                            v-if="can('agencies:manage')"
                            icon="pi pi-pencil"
                            severity="warn"
                            text
                            rounded
                            size="small"
                            @click="openEditModal(data)"
                        />
                        <Button
                            v-if="can('agencies:delete')"
                            icon="pi pi-trash"
                            severity="danger"
                            text
                            rounded
                            size="small"
                            @click="deleteAgency(data.id)"
                        />
                    </div>
                </template>
            </Column>

            <template #empty>
                <div class="py-8 text-center text-gray-500">No agencies found.</div>
            </template>
        </DataTable>
    </div>

    <!-- Create / Edit Modal -->
    <Dialog
        v-model:visible="showFormModal"
        :header="editingAgency ? 'Edit Agency' : 'Create Agency'"
        modal
        :style="{ width: '600px' }"
        data-testid="agency-form-modal"
    >
        <form @submit.prevent="submitForm" class="flex flex-col gap-4">
            <div>
                <label for="name" class="mb-1 block text-sm font-medium">Name *</label>
                <InputText id="name" v-model="form.name" class="w-full" :invalid="!!form.errors.name" data-testid="agency-name" />
                <small v-if="form.errors.name" class="text-red-500">{{ form.errors.name }}</small>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="email" class="mb-1 block text-sm font-medium">Email</label>
                    <InputText id="email" v-model="form.email" class="w-full" :invalid="!!form.errors.email" />
                    <small v-if="form.errors.email" class="text-red-500">{{ form.errors.email }}</small>
                </div>
                <div>
                    <label for="phone" class="mb-1 block text-sm font-medium">Phone</label>
                    <InputText id="phone" v-model="form.phone" class="w-full" :invalid="!!form.errors.phone" />
                    <small v-if="form.errors.phone" class="text-red-500">{{ form.errors.phone }}</small>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="website" class="mb-1 block text-sm font-medium">Website</label>
                    <InputText id="website" v-model="form.website" class="w-full" :invalid="!!form.errors.website" />
                    <small v-if="form.errors.website" class="text-red-500">{{ form.errors.website }}</small>
                </div>
                <div>
                    <label for="contact_person" class="mb-1 block text-sm font-medium">Contact Person</label>
                    <InputText id="contact_person" v-model="form.contact_person" class="w-full" />
                </div>
            </div>

            <div>
                <label for="address" class="mb-1 block text-sm font-medium">Address *</label>
                <Textarea id="address" v-model="form.address" class="w-full" rows="2" :invalid="!!form.errors.address" />
                <small v-if="form.errors.address" class="text-red-500">{{ form.errors.address }}</small>
            </div>

            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label for="canton_id" class="mb-1 block text-sm font-medium">Canton *</label>
                    <Select
                        id="canton_id"
                        v-model="form.canton_id"
                        :options="cantons"
                        optionLabel="code"
                        optionValue="id"
                        placeholder="Select..."
                        class="w-full"
                        :invalid="!!form.errors.canton_id"
                    />
                    <small v-if="form.errors.canton_id" class="text-red-500">{{ form.errors.canton_id }}</small>
                </div>
                <div>
                    <label for="city_id" class="mb-1 block text-sm font-medium">City *</label>
                    <InputNumber id="city_id" v-model="form.city_id" class="w-full" placeholder="City ID" :invalid="!!form.errors.city_id" :useGrouping="false" />
                    <small v-if="form.errors.city_id" class="text-red-500">{{ form.errors.city_id }}</small>
                </div>
                <div>
                    <label for="postal_code" class="mb-1 block text-sm font-medium">Postal Code</label>
                    <InputText id="postal_code" v-model="form.postal_code" class="w-full" />
                </div>
            </div>

            <div v-if="editingAgency">
                <label for="status" class="mb-1 block text-sm font-medium">Status</label>
                <Select
                    id="status"
                    v-model="form.status"
                    :options="statusOptions"
                    optionLabel="label"
                    optionValue="value"
                    class="w-full"
                />
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <Button label="Cancel" severity="secondary" text @click="showFormModal = false" />
                <Button
                    type="submit"
                    :label="editingAgency ? 'Update' : 'Create'"
                    :loading="form.processing"
                    data-testid="save-button"
                />
            </div>
        </form>
    </Dialog>
</template>
