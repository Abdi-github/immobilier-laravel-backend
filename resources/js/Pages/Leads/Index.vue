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
import Tag from 'primevue/tag';
import type { PaginatedData, Lead, Agency, User } from '@/Types/models';
import { LeadStatus, LeadPriority } from '@/Types/enums';
import { useConfirm } from '@/Composables/useConfirm';
import { usePermissions } from '@/Composables/usePermissions';
import { useDebounceFn } from '@vueuse/core';

defineOptions({ layout: AdminLayout });

interface Props {
    leads: PaginatedData<Lead>;
    filters: Record<string, string>;
    stats: { total: number; new: number; needs_follow_up: number; unassigned: number };
    agencies: Pick<Agency, 'id' | 'name'>[];
    agents: Pick<User, 'id' | 'first_name' | 'last_name'>[];
}

const props = defineProps<Props>();

const { can } = usePermissions();
const { confirmDelete } = useConfirm();

// Filters
const search = ref(props.filters.search ?? '');
const statusFilter = ref<string | null>(props.filters.status ?? null);
const priorityFilter = ref<string | null>(props.filters.priority ?? null);
const sourceFilter = ref<string | null>(props.filters.source ?? null);
const agencyFilter = ref<number | null>(props.filters.agency_id ? Number(props.filters.agency_id) : null);

const statusOptions = [
    { label: 'New', value: LeadStatus.NEW },
    { label: 'Contacted', value: LeadStatus.CONTACTED },
    { label: 'Qualified', value: LeadStatus.QUALIFIED },
    { label: 'Viewing Scheduled', value: LeadStatus.VIEWING_SCHEDULED },
    { label: 'Negotiating', value: LeadStatus.NEGOTIATING },
    { label: 'Won', value: LeadStatus.WON },
    { label: 'Lost', value: LeadStatus.LOST },
    { label: 'Archived', value: LeadStatus.ARCHIVED },
];

const priorityOptions = [
    { label: 'Low', value: LeadPriority.LOW },
    { label: 'Medium', value: LeadPriority.MEDIUM },
    { label: 'High', value: LeadPriority.HIGH },
    { label: 'Urgent', value: LeadPriority.URGENT },
];

const sourceOptions = [
    { label: 'Website', value: 'website' },
    { label: 'Phone', value: 'phone' },
    { label: 'Email', value: 'email' },
    { label: 'Referral', value: 'referral' },
    { label: 'Walk-in', value: 'walk_in' },
    { label: 'Social Media', value: 'social_media' },
    { label: 'Other', value: 'other' },
];

function prioritySeverity(priority: string): 'success' | 'info' | 'warn' | 'danger' | 'secondary' {
    const map: Record<string, 'success' | 'info' | 'warn' | 'danger'> = {
        low: 'info',
        medium: 'warn',
        high: 'danger',
        urgent: 'danger',
    };
    return map[priority] ?? 'secondary';
}

function reload(extra: Record<string, unknown> = {}) {
    router.get(route('admin.leads.index'), {
        search: search.value || undefined,
        status: statusFilter.value || undefined,
        priority: priorityFilter.value || undefined,
        source: sourceFilter.value || undefined,
        agency_id: agencyFilter.value || undefined,
        ...extra,
    }, { preserveState: true, preserveScroll: true });
}

const debouncedSearch = useDebounceFn(() => reload(), 400);

watch(search, () => debouncedSearch());
watch([statusFilter, priorityFilter, sourceFilter, agencyFilter], () => reload());

function onPage(event: { page: number; rows: number }) {
    reload({ page: event.page + 1, limit: event.rows });
}

function clearFilters() {
    search.value = '';
    statusFilter.value = null;
    priorityFilter.value = null;
    sourceFilter.value = null;
    agencyFilter.value = null;
    reload();
}

function deleteLead(id: number) {
    confirmDelete(() => {
        router.delete(route('admin.leads.destroy', id));
    });
}

function formatDate(iso: string | null): string {
    if (!iso) return '–';
    return new Date(iso).toLocaleDateString('en-CH', { year: 'numeric', month: 'short', day: 'numeric' });
}
</script>

<template>
    <PageHeader title="Leads" description="Manage property inquiries and leads" />

    <!-- Stats Summary -->
    <div class="mb-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
        <div class="rounded-lg border border-gray-200 bg-white p-4">
            <div class="text-sm text-gray-500">Total Leads</div>
            <div class="text-2xl font-bold">{{ stats.total }}</div>
        </div>
        <div class="rounded-lg border border-blue-200 bg-blue-50 p-4">
            <div class="text-sm text-blue-600">New</div>
            <div class="text-2xl font-bold text-blue-700">{{ stats.new }}</div>
        </div>
        <div class="rounded-lg border border-orange-200 bg-orange-50 p-4">
            <div class="text-sm text-orange-600">Needs Follow-up</div>
            <div class="text-2xl font-bold text-orange-700">{{ stats.needs_follow_up }}</div>
        </div>
        <div class="rounded-lg border border-red-200 bg-red-50 p-4">
            <div class="text-sm text-red-600">Unassigned</div>
            <div class="text-2xl font-bold text-red-700">{{ stats.unassigned }}</div>
        </div>
    </div>

    <!-- Filters -->
    <div class="mb-4 rounded-lg border border-gray-200 bg-white p-4">
        <div class="flex flex-wrap items-center gap-3">
            <span class="p-input-icon-left">
                <i class="pi pi-search" />
                <InputText v-model="search" placeholder="Search leads..." class="w-64" />
            </span>

            <Select
                v-model="statusFilter"
                :options="statusOptions"
                optionLabel="label"
                optionValue="value"
                placeholder="All Statuses"
                showClear
                class="w-48"
            />

            <Select
                v-model="priorityFilter"
                :options="priorityOptions"
                optionLabel="label"
                optionValue="value"
                placeholder="All Priorities"
                showClear
                class="w-40"
            />

            <Select
                v-model="sourceFilter"
                :options="sourceOptions"
                optionLabel="label"
                optionValue="value"
                placeholder="All Sources"
                showClear
                class="w-40"
            />

            <Select
                v-model="agencyFilter"
                :options="agencies"
                optionLabel="name"
                optionValue="id"
                placeholder="All Agencies"
                showClear
                class="w-44"
            />

            <Button label="Clear" icon="pi pi-filter-slash" severity="secondary" text @click="clearFilters" />
        </div>
    </div>

    <!-- DataTable -->
    <div class="rounded-lg border border-gray-200 bg-white">
        <DataTable
            :value="leads.data"
            :lazy="true"
            :paginator="true"
            :rows="leads.meta.per_page"
            :totalRecords="leads.meta.total"
            :rowsPerPageOptions="[10, 15, 25, 50]"
            paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
            @page="onPage"
            stripedRows
            responsiveLayout="scroll"
            data-testid="leads-table"
        >
            <Column field="contact" header="Contact" style="min-width: 180px">
                <template #body="{ data }">
                    <Link :href="route('admin.leads.show', data.id)" class="font-medium text-blue-600 hover:underline">
                        {{ data.contact_first_name }} {{ data.contact_last_name }}
                    </Link>
                    <div class="text-xs text-gray-500">{{ data.contact_email }}</div>
                </template>
            </Column>

            <Column field="property" header="Property" style="min-width: 180px">
                <template #body="{ data }">
                    <template v-if="data.property">
                        <Link :href="route('admin.properties.show', data.property.id)" class="text-sm text-blue-600 hover:underline">
                            {{ data.property.address }}
                        </Link>
                        <div class="text-xs text-gray-500">
                            {{ data.property.category?.name ?? '' }} · {{ data.property.canton?.code ?? '' }}
                        </div>
                    </template>
                    <span v-else class="text-gray-400">–</span>
                </template>
            </Column>

            <Column field="status" header="Status" style="min-width: 130px">
                <template #body="{ data }">
                    <StatusBadge :status="data.status" type="lead" />
                </template>
            </Column>

            <Column field="priority" header="Priority" style="min-width: 100px">
                <template #body="{ data }">
                    <Tag :value="data.priority" :severity="prioritySeverity(data.priority)" />
                </template>
            </Column>

            <Column field="source" header="Source" style="min-width: 100px">
                <template #body="{ data }">
                    <span class="text-sm capitalize">{{ data.source?.replace(/_/g, ' ') ?? '–' }}</span>
                </template>
            </Column>

            <Column field="agency" header="Agency" style="min-width: 120px">
                <template #body="{ data }">
                    {{ data.agency?.name ?? '–' }}
                </template>
            </Column>

            <Column field="assigned_user" header="Assigned To" style="min-width: 130px">
                <template #body="{ data }">
                    <template v-if="data.assigned_user">
                        {{ data.assigned_user.first_name }} {{ data.assigned_user.last_name }}
                    </template>
                    <Tag v-else value="Unassigned" severity="warn" />
                </template>
            </Column>

            <Column field="created_at" header="Created" style="min-width: 110px">
                <template #body="{ data }">
                    {{ formatDate(data.created_at) }}
                </template>
            </Column>

            <Column header="Actions" style="min-width: 100px" :exportable="false">
                <template #body="{ data }">
                    <div class="flex items-center gap-1">
                        <Link :href="route('admin.leads.show', data.id)">
                            <Button icon="pi pi-eye" severity="info" text rounded size="small" />
                        </Link>
                        <Button
                            v-if="can('leads:delete')"
                            icon="pi pi-trash"
                            severity="danger"
                            text
                            rounded
                            size="small"
                            @click="deleteLead(data.id)"
                        />
                    </div>
                </template>
            </Column>

            <template #empty>
                <div class="py-8 text-center text-gray-500">No leads found.</div>
            </template>
        </DataTable>
    </div>
</template>
