<script setup lang="ts">
import { ref } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PageHeader from '@/Components/Shared/PageHeader.vue';
import StatusBadge from '@/Components/Shared/StatusBadge.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import Textarea from 'primevue/textarea';
import type { PaginatedData, Property } from '@/Types/models';

defineOptions({ layout: AdminLayout });

interface Props {
    properties: PaginatedData<Property>;
}

const props = defineProps<Props>();

const showRejectDialog = ref(false);
const rejectingPropertyId = ref<number | null>(null);
const rejectionReason = ref('');

function approve(id: number) {
    router.post(route('admin.properties.approve', id));
}

function openRejectDialog(id: number) {
    rejectingPropertyId.value = id;
    rejectionReason.value = '';
    showRejectDialog.value = true;
}

function reject() {
    if (!rejectingPropertyId.value) return;
    router.post(route('admin.properties.reject', rejectingPropertyId.value), {
        rejection_reason: rejectionReason.value,
    }, {
        onSuccess: () => { showRejectDialog.value = false; },
    });
}

function onPage(event: { page: number; rows: number }) {
    router.get(route('admin.properties.pending'), {
        page: event.page + 1,
        limit: event.rows,
    }, { preserveState: true });
}

function formatPrice(price: number, currency: string) {
    return new Intl.NumberFormat('de-CH', { style: 'currency', currency: currency || 'CHF' }).format(price);
}
</script>

<template>
    <PageHeader title="Pending Properties" description="Review and approve property submissions">
        <template #actions>
            <Link :href="route('admin.properties.index')">
                <Button label="All Properties" icon="pi pi-list" severity="secondary" text />
            </Link>
        </template>
    </PageHeader>

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

            <Column field="status" header="Status" style="min-width: 120px">
                <template #body="{ data }">
                    <StatusBadge :status="data.status" type="property" />
                </template>
            </Column>

            <Column field="price" header="Price" style="min-width: 120px">
                <template #body="{ data }">
                    {{ formatPrice(data.price, data.currency) }}
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

            <Column field="owner" header="Submitted By" style="min-width: 140px">
                <template #body="{ data }">
                    {{ data.owner ? `${data.owner.first_name} ${data.owner.last_name}` : '–' }}
                </template>
            </Column>

            <Column field="created_at" header="Submitted" style="min-width: 120px">
                <template #body="{ data }">
                    {{ new Date(data.created_at).toLocaleDateString() }}
                </template>
            </Column>

            <Column header="Actions" style="min-width: 160px" :exportable="false">
                <template #body="{ data }">
                    <div class="flex items-center gap-1">
                        <Button label="Approve" icon="pi pi-check" severity="success" size="small" @click="approve(data.id)" />
                        <Button label="Reject" icon="pi pi-times" severity="danger" size="small" outlined @click="openRejectDialog(data.id)" />
                    </div>
                </template>
            </Column>

            <template #empty>
                <div class="py-8 text-center text-gray-500">
                    <i class="pi pi-check-circle mb-2 text-4xl text-green-400" />
                    <p>No properties pending approval.</p>
                </div>
            </template>
        </DataTable>
    </div>

    <!-- Reject Dialog -->
    <Dialog v-model:visible="showRejectDialog" header="Reject Property" :modal="true" :style="{ width: '450px' }">
        <p class="mb-4 text-gray-600">Please provide a reason for rejecting this property.</p>
        <Textarea v-model="rejectionReason" rows="4" class="w-full" placeholder="Rejection reason..." />
        <template #footer>
            <Button label="Cancel" severity="secondary" text @click="showRejectDialog = false" />
            <Button label="Reject" severity="danger" icon="pi pi-times" :disabled="!rejectionReason.trim()" @click="reject" />
        </template>
    </Dialog>
</template>
