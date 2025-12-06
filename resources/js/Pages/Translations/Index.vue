<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PageHeader from '@/Components/Shared/PageHeader.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Select from 'primevue/select';
import InputText from 'primevue/inputtext';
import Tag from 'primevue/tag';
import Dialog from 'primevue/dialog';
import Textarea from 'primevue/textarea';
import type { PropertyTranslation, PaginatedData } from '@/Types/models';
import { usePermissions } from '@/Composables/usePermissions';
import { useConfirm } from '@/Composables/useConfirm';
import { useDebounceFn } from '@vueuse/core';

defineOptions({ layout: AdminLayout });

interface TranslationRow extends PropertyTranslation {
    rejection_reason?: string;
    property?: { id: number; external_id: string; source_language: string };
    approvedByUser?: { id: number; first_name: string; last_name: string };
}

interface Props {
    translations: {
        data: TranslationRow[];
        meta: { current_page: number; from: number | null; last_page: number; per_page: number; to: number | null; total: number };
    };
    filters: Record<string, string>;
    stats: {
        total: number;
        by_status: Record<string, number>;
    };
}

const props = defineProps<Props>();
const { can } = usePermissions();
const { confirmDelete } = useConfirm();

// Filters
const search = ref(props.filters.search ?? '');
const languageFilter = ref(props.filters.language ?? '');
const sourceFilter = ref(props.filters.source ?? '');
const statusFilter = ref(props.filters.approval_status ?? '');

const languageOptions = [
    { label: 'All Languages', value: '' },
    { label: 'English', value: 'en' },
    { label: 'French', value: 'fr' },
    { label: 'German', value: 'de' },
    { label: 'Italian', value: 'it' },
];

const sourceOptions = [
    { label: 'All Sources', value: '' },
    { label: 'Original', value: 'original' },
    { label: 'DeepL', value: 'deepl' },
    { label: 'LibreTranslate', value: 'libretranslate' },
    { label: 'Human', value: 'human' },
];

const statusOptions = [
    { label: 'All Statuses', value: '' },
    { label: 'Pending', value: 'PENDING' },
    { label: 'Approved', value: 'APPROVED' },
    { label: 'Rejected', value: 'REJECTED' },
];

function applyFilters(extra: Record<string, unknown> = {}) {
    router.get(route('admin.translations.index'), {
        search: search.value || undefined,
        language: languageFilter.value || undefined,
        source: sourceFilter.value || undefined,
        approval_status: statusFilter.value || undefined,
        ...extra,
    }, { preserveState: true, preserveScroll: true });
}

const debouncedSearch = useDebounceFn(() => applyFilters(), 400);
watch(search, () => debouncedSearch());
watch([languageFilter, sourceFilter, statusFilter], () => applyFilters());

function clearFilters() {
    search.value = '';
    languageFilter.value = '';
    sourceFilter.value = '';
    statusFilter.value = '';
    applyFilters();
}

function onPage(event: { page: number; rows: number }) {
    applyFilters({ page: event.page + 1, limit: event.rows });
}

// Status helpers
function statusSeverity(status: string): string {
    const map: Record<string, string> = { PENDING: 'warn', APPROVED: 'success', REJECTED: 'danger' };
    return map[status] ?? 'secondary';
}

function languageLabel(code: string): string {
    const map: Record<string, string> = { en: 'EN', fr: 'FR', de: 'DE', it: 'IT' };
    return map[code] ?? code.toUpperCase();
}

function sourceSeverity(source: string): string {
    const map: Record<string, string> = { original: 'info', human: 'success', deepl: 'warn', libretranslate: 'secondary' };
    return map[source] ?? 'secondary';
}

// Selection
const selectedRows = ref<TranslationRow[]>([]);

const canBulkApprove = computed(() =>
    selectedRows.value.length > 0 && selectedRows.value.every(r => r.approval_status !== 'APPROVED'),
);

function bulkApprove() {
    if (!canBulkApprove.value) return;
    router.post(route('admin.translations.bulk-approve'), {
        ids: selectedRows.value.map(r => r.id),
    }, { preserveScroll: true, onSuccess: () => { selectedRows.value = []; } });
}

// View modal
const showViewModal = ref(false);
const viewTranslation = ref<TranslationRow | null>(null);

function openView(row: TranslationRow) {
    viewTranslation.value = row;
    showViewModal.value = true;
}

// Reject modal
const showRejectModal = ref(false);
const rejectingId = ref<number | null>(null);
const rejectForm = useForm({ rejection_reason: '' });

function openReject(id: number) {
    rejectingId.value = id;
    rejectForm.reset();
    rejectForm.clearErrors();
    showRejectModal.value = true;
}

function submitReject() {
    if (!rejectingId.value) return;
    rejectForm.post(route('admin.translations.reject', rejectingId.value), {
        preserveScroll: true,
        onSuccess: () => { showRejectModal.value = false; },
    });
}

function approveOne(id: number) {
    router.post(route('admin.translations.approve', id), {}, { preserveScroll: true });
}

function resetOne(id: number) {
    router.post(route('admin.translations.reset', id), {}, { preserveScroll: true });
}

function deleteOne(id: number) {
    confirmDelete(() => {
        router.delete(route('admin.translations.destroy', id), { preserveScroll: true });
    });
}
</script>

<template>
    <PageHeader title="Translations" description="Manage property translations">
        <template #actions>
            <Button
                v-if="can('translations:approve') && canBulkApprove"
                :label="`Approve ${selectedRows.length} selected`"
                icon="pi pi-check-circle"
                severity="success"
                @click="bulkApprove"
            />
        </template>
    </PageHeader>

    <!-- Stats Cards -->
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-4">
        <div class="rounded-lg border border-gray-200 bg-white p-4 text-center">
            <div class="text-2xl font-bold">{{ stats.total }}</div>
            <div class="text-sm text-gray-500">Total</div>
        </div>
        <div class="rounded-lg border border-yellow-200 bg-yellow-50 p-4 text-center">
            <div class="text-2xl font-bold text-yellow-700">{{ stats.by_status.PENDING ?? 0 }}</div>
            <div class="text-sm text-yellow-600">Pending</div>
        </div>
        <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-center">
            <div class="text-2xl font-bold text-green-700">{{ stats.by_status.APPROVED ?? 0 }}</div>
            <div class="text-sm text-green-600">Approved</div>
        </div>
        <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-center">
            <div class="text-2xl font-bold text-red-700">{{ stats.by_status.REJECTED ?? 0 }}</div>
            <div class="text-sm text-red-600">Rejected</div>
        </div>
    </div>

    <!-- Filters -->
    <div class="mb-4 flex flex-wrap items-center gap-3">
        <span class="p-input-icon-left">
            <i class="pi pi-search" />
            <InputText v-model="search" placeholder="Search translations..." class="w-64" />
        </span>

        <Select v-model="languageFilter" :options="languageOptions" optionLabel="label" optionValue="value" class="w-40" />
        <Select v-model="sourceFilter" :options="sourceOptions" optionLabel="label" optionValue="value" class="w-44" />
        <Select v-model="statusFilter" :options="statusOptions" optionLabel="label" optionValue="value" class="w-40" />

        <Button label="Clear" icon="pi pi-filter-slash" severity="secondary" text @click="clearFilters" />
    </div>

    <!-- Data Table -->
    <div class="rounded-lg border border-gray-200 bg-white">
        <DataTable
            v-model:selection="selectedRows"
            :value="translations.data"
            :lazy="true"
            :paginator="true"
            :rows="translations.meta.per_page"
            :totalRecords="translations.meta.total"
            :rowsPerPageOptions="[10, 20, 50]"
            paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
            @page="onPage"
            stripedRows
            responsiveLayout="scroll"
            data-testid="translations-table"
        >
            <Column selectionMode="multiple" headerStyle="width: 3rem" v-if="can('translations:approve')" />

            <Column field="property" header="Property" style="min-width: 150px">
                <template #body="{ data }">
                    <span v-if="data.property" class="font-mono text-sm">{{ data.property.external_id }}</span>
                    <span v-else class="text-gray-400">–</span>
                </template>
            </Column>

            <Column field="language" header="Lang" style="min-width: 70px">
                <template #body="{ data }">
                    <Tag :value="languageLabel(data.language)" severity="info" />
                </template>
            </Column>

            <Column field="title" header="Title" style="min-width: 250px">
                <template #body="{ data }">
                    <div class="max-w-xs truncate" :title="data.title">{{ data.title }}</div>
                </template>
            </Column>

            <Column field="source" header="Source" style="min-width: 110px">
                <template #body="{ data }">
                    <Tag :value="data.source ?? 'unknown'" :severity="sourceSeverity(data.source ?? '')" />
                </template>
            </Column>

            <Column field="quality_score" header="Quality" style="min-width: 80px">
                <template #body="{ data }">
                    <span v-if="data.quality_score != null">{{ data.quality_score }}%</span>
                    <span v-else class="text-gray-400">–</span>
                </template>
            </Column>

            <Column field="approval_status" header="Status" style="min-width: 100px">
                <template #body="{ data }">
                    <Tag :value="data.approval_status" :severity="statusSeverity(data.approval_status)" />
                </template>
            </Column>

            <Column header="Actions" style="min-width: 150px" :exportable="false">
                <template #body="{ data }">
                    <div class="flex items-center gap-1">
                        <Button icon="pi pi-eye" severity="info" text rounded size="small" @click="openView(data)" />
                        <Button
                            v-if="can('translations:approve') && data.approval_status !== 'APPROVED'"
                            icon="pi pi-check"
                            severity="success"
                            text
                            rounded
                            size="small"
                            @click="approveOne(data.id)"
                        />
                        <Button
                            v-if="can('translations:approve') && data.approval_status !== 'REJECTED'"
                            icon="pi pi-times"
                            severity="danger"
                            text
                            rounded
                            size="small"
                            @click="openReject(data.id)"
                        />
                        <Button
                            v-if="can('translations:approve') && data.approval_status !== 'PENDING'"
                            icon="pi pi-replay"
                            severity="warn"
                            text
                            rounded
                            size="small"
                            @click="resetOne(data.id)"
                        />
                        <Button
                            v-if="can('translations:create')"
                            icon="pi pi-trash"
                            severity="danger"
                            text
                            rounded
                            size="small"
                            @click="deleteOne(data.id)"
                        />
                    </div>
                </template>
            </Column>

            <template #empty>
                <div class="py-8 text-center text-gray-500">No translations found.</div>
            </template>
        </DataTable>
    </div>

    <!-- View Modal -->
    <Dialog
        v-model:visible="showViewModal"
        header="Translation Details"
        modal
        :style="{ width: '700px' }"
        data-testid="translation-view-modal"
    >
        <template v-if="viewTranslation">
            <div class="space-y-4">
                <div class="flex flex-wrap gap-4">
                    <div>
                        <span class="text-sm text-gray-500">Property:</span>
                        <span class="ml-1 font-mono">{{ viewTranslation.property?.external_id ?? '–' }}</span>
                    </div>
                    <div>
                        <span class="text-sm text-gray-500">Language:</span>
                        <Tag :value="languageLabel(viewTranslation.language)" severity="info" class="ml-1" />
                    </div>
                    <div>
                        <span class="text-sm text-gray-500">Source:</span>
                        <Tag :value="viewTranslation.source ?? 'unknown'" :severity="sourceSeverity(viewTranslation.source ?? '')" class="ml-1" />
                    </div>
                    <div>
                        <span class="text-sm text-gray-500">Status:</span>
                        <Tag :value="viewTranslation.approval_status ?? 'PENDING'" :severity="statusSeverity(viewTranslation.approval_status ?? 'PENDING')" class="ml-1" />
                    </div>
                    <div v-if="viewTranslation.quality_score != null">
                        <span class="text-sm text-gray-500">Quality:</span>
                        <span class="ml-1 font-medium">{{ viewTranslation.quality_score }}%</span>
                    </div>
                </div>

                <div>
                    <h4 class="mb-1 text-sm font-medium text-gray-500">Title</h4>
                    <p class="rounded bg-gray-50 p-3">{{ viewTranslation.title }}</p>
                </div>

                <div>
                    <h4 class="mb-1 text-sm font-medium text-gray-500">Description</h4>
                    <p class="max-h-60 overflow-y-auto whitespace-pre-wrap rounded bg-gray-50 p-3">{{ viewTranslation.description ?? '–' }}</p>
                </div>

                <div v-if="viewTranslation.approval_status === 'REJECTED' && viewTranslation.rejection_reason" class="rounded border border-red-200 bg-red-50 p-3">
                    <h4 class="mb-1 text-sm font-medium text-red-700">Rejection Reason</h4>
                    <p class="text-red-600">{{ viewTranslation.rejection_reason }}</p>
                </div>

                <div v-if="viewTranslation.approvedByUser" class="text-sm text-gray-500">
                    Reviewed by {{ viewTranslation.approvedByUser.first_name }} {{ viewTranslation.approvedByUser.last_name }}
                </div>
            </div>
        </template>

        <template #footer>
            <div class="flex justify-end gap-2">
                <Button
                    v-if="can('translations:approve') && viewTranslation?.approval_status !== 'APPROVED'"
                    label="Approve"
                    icon="pi pi-check"
                    severity="success"
                    @click="() => { approveOne(viewTranslation!.id); showViewModal = false; }"
                />
                <Button
                    v-if="can('translations:approve') && viewTranslation?.approval_status !== 'REJECTED'"
                    label="Reject"
                    icon="pi pi-times"
                    severity="danger"
                    @click="() => { showViewModal = false; openReject(viewTranslation!.id); }"
                />
                <Button label="Close" severity="secondary" @click="showViewModal = false" />
            </div>
        </template>
    </Dialog>

    <!-- Reject Modal -->
    <Dialog
        v-model:visible="showRejectModal"
        header="Reject Translation"
        modal
        :style="{ width: '450px' }"
        data-testid="translation-reject-modal"
    >
        <form @submit.prevent="submitReject" class="flex flex-col gap-4">
            <div>
                <label class="mb-1 block text-sm font-medium">Reason *</label>
                <Textarea
                    v-model="rejectForm.rejection_reason"
                    rows="4"
                    class="w-full"
                    placeholder="Explain why this translation is being rejected..."
                    :invalid="!!rejectForm.errors.rejection_reason"
                />
                <small v-if="rejectForm.errors.rejection_reason" class="text-red-500">{{ rejectForm.errors.rejection_reason }}</small>
            </div>
            <div class="flex justify-end gap-2">
                <Button label="Cancel" severity="secondary" text @click="showRejectModal = false" />
                <Button type="submit" label="Reject" severity="danger" :loading="rejectForm.processing" />
            </div>
        </form>
    </Dialog>
</template>
