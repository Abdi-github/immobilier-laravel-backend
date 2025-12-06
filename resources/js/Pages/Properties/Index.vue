<script setup lang="ts">
import { ref, watch } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PageHeader from '@/Components/Shared/PageHeader.vue';
import StatusBadge from '@/Components/Shared/StatusBadge.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Button from 'primevue/button';
import type { PaginatedData, Property, Category, Canton } from '@/Types/models';
import { useConfirm } from '@/Composables/useConfirm';
import { usePermissions } from '@/Composables/usePermissions';
import { useDebounceFn } from '@vueuse/core';

defineOptions({ layout: AdminLayout });

interface Props {
    properties: PaginatedData<Property>;
    filters: Record<string, string>;
    statuses: { value: string; label: string }[];
    categories: Category[];
    cantons: Canton[];
}

const props = defineProps<Props>();

const { can } = usePermissions();
const { confirmDelete } = useConfirm();

const search = ref(props.filters.search ?? '');
const statusFilter = ref<string | null>(props.filters.status ?? null);
const categoryFilter = ref<number | null>(props.filters.category_id ? Number(props.filters.category_id) : null);
const cantonFilter = ref<number | null>(props.filters.canton_id ? Number(props.filters.canton_id) : null);
const transactionFilter = ref<string | null>(props.filters.transaction_type ?? null);

function reload(extra: Record<string, unknown> = {}) {
    router.get(route('admin.properties.index'), {
        search: search.value || undefined,
        status: statusFilter.value || undefined,
        category_id: categoryFilter.value || undefined,
        canton_id: cantonFilter.value || undefined,
        transaction_type: transactionFilter.value || undefined,
        ...extra,
    }, { preserveState: true, preserveScroll: true });
}

const debouncedSearch = useDebounceFn(() => reload(), 400);

watch(search, () => debouncedSearch());
watch([statusFilter, categoryFilter, cantonFilter, transactionFilter], () => reload());

function onPage(event: { page: number; rows: number }) {
    reload({ page: event.page + 1, limit: event.rows });
}

function clearFilters() {
    search.value = '';
    statusFilter.value = null;
    categoryFilter.value = null;
    cantonFilter.value = null;
    transactionFilter.value = null;
    reload();
}

function deleteProperty(id: number) {
    confirmDelete(() => {
        router.delete(route('admin.properties.destroy', id));
    });
}

function formatPrice(price: number, currency: string) {
    return new Intl.NumberFormat('de-CH', { style: 'currency', currency: currency || 'CHF' }).format(price);
}
</script>

<template>
    <PageHeader title="Properties" description="Manage all property listings">
        <template #actions>
            <Link v-if="can('properties:create')" :href="route('admin.properties.create')">
                <Button label="Add Property" icon="pi pi-plus" />
            </Link>
        </template>
    </PageHeader>

    <!-- Filters -->
    <div class="mb-4 rounded-lg border border-gray-200 bg-white p-4">
        <div class="flex flex-wrap items-center gap-3">
            <span class="p-input-icon-left">
                <i class="pi pi-search" />
                <InputText v-model="search" placeholder="Search properties..." class="w-64" />
            </span>

            <Select
                v-model="statusFilter"
                :options="statuses"
                optionLabel="label"
                optionValue="value"
                placeholder="All Statuses"
                showClear
                class="w-48"
            />

            <Select
                v-model="categoryFilter"
                :options="categories"
                optionLabel="name"
                optionValue="id"
                placeholder="All Categories"
                showClear
                class="w-48"
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
                v-model="transactionFilter"
                :options="[{ label: 'Rent', value: 'rent' }, { label: 'Buy', value: 'buy' }]"
                optionLabel="label"
                optionValue="value"
                placeholder="All Types"
                showClear
                class="w-36"
            />

            <Button label="Clear" icon="pi pi-filter-slash" severity="secondary" text @click="clearFilters" />
        </div>
    </div>

    <!-- DataTable -->
    <div class="rounded-lg border border-gray-200 bg-white">
        <DataTable
            :value="properties.data"
            :lazy="true"
            :paginator="true"
            :rows="properties.meta.per_page"
            :totalRecords="properties.meta.total"
            :rowsPerPageOptions="[10, 15, 25, 50]"
            paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
            @page="onPage"
            stripedRows
            responsiveLayout="scroll"
        >
            <Column field="title" header="Title" style="min-width: 200px">
                <template #body="{ data }">
                    <Link :href="route('admin.properties.show', data.id)" class="font-medium text-blue-600 hover:underline">
                        {{ data.title || data.external_id }}
                    </Link>
                </template>
            </Column>

            <Column field="status" header="Status" style="min-width: 140px">
                <template #body="{ data }">
                    <StatusBadge :status="data.status" type="property" />
                </template>
            </Column>

            <Column field="transaction_type" header="Type" style="min-width: 80px">
                <template #body="{ data }">
                    <span class="rounded-full px-2 py-1 text-xs font-medium" :class="data.transaction_type === 'rent' ? 'bg-blue-100 text-blue-700' : 'bg-green-100 text-green-700'">
                        {{ data.transaction_type === 'rent' ? 'Rent' : 'Buy' }}
                    </span>
                </template>
            </Column>

            <Column field="price" header="Price" style="min-width: 120px">
                <template #body="{ data }">
                    {{ formatPrice(data.price, data.currency) }}
                    <span v-if="data.transaction_type === 'rent'" class="text-xs text-gray-500">/mo</span>
                </template>
            </Column>

            <Column field="rooms" header="Rooms" style="min-width: 70px">
                <template #body="{ data }">
                    {{ data.rooms ?? '–' }}
                </template>
            </Column>

            <Column field="category" header="Category" style="min-width: 120px">
                <template #body="{ data }">
                    {{ data.category?.name ?? '–' }}
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

            <Column header="Actions" style="min-width: 120px" :exportable="false">
                <template #body="{ data }">
                    <div class="flex items-center gap-1">
                        <Link :href="route('admin.properties.show', data.id)">
                            <Button icon="pi pi-eye" severity="info" text rounded size="small" />
                        </Link>
                        <Link v-if="can('properties:update')" :href="route('admin.properties.edit', data.id)">
                            <Button icon="pi pi-pencil" severity="warn" text rounded size="small" />
                        </Link>
                        <Button
                            v-if="can('properties:delete')"
                            icon="pi pi-trash"
                            severity="danger"
                            text
                            rounded
                            size="small"
                            @click="deleteProperty(data.id)"
                        />
                    </div>
                </template>
            </Column>

            <template #empty>
                <div class="py-8 text-center text-gray-500">No properties found.</div>
            </template>
        </DataTable>
    </div>
</template>
