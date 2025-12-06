<script setup lang="ts">
import { router, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PageHeader from '@/Components/Shared/PageHeader.vue';
import StatusBadge from '@/Components/Shared/StatusBadge.vue';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Dialog from 'primevue/dialog';
import Textarea from 'primevue/textarea';
import Image from 'primevue/image';
import { ref } from 'vue';
import type { Property, PropertyImage } from '@/Types/models';
import { PropertyStatus } from '@/Types/enums';
import { usePermissions } from '@/Composables/usePermissions';
import { useConfirm } from '@/Composables/useConfirm';

defineOptions({ layout: AdminLayout });

interface Props {
    property: Property;
    images: PropertyImage[];
}

const props = defineProps<Props>();

const { can } = usePermissions();
const { confirmDelete } = useConfirm();

const showRejectDialog = ref(false);
const rejectionReason = ref('');

function submitForApproval() {
    router.post(route('admin.properties.submit', props.property.id));
}

function approve() {
    router.post(route('admin.properties.approve', props.property.id));
}

function openRejectDialog() {
    rejectionReason.value = '';
    showRejectDialog.value = true;
}

function reject() {
    router.post(route('admin.properties.reject', props.property.id), {
        rejection_reason: rejectionReason.value,
    }, {
        onSuccess: () => { showRejectDialog.value = false; },
    });
}

function publish() {
    router.post(route('admin.properties.publish', props.property.id));
}

function archive() {
    router.post(route('admin.properties.archive', props.property.id));
}

function deleteProperty() {
    confirmDelete(() => {
        router.delete(route('admin.properties.destroy', props.property.id));
    });
}

function formatPrice(price: number, currency: string) {
    return new Intl.NumberFormat('de-CH', { style: 'currency', currency: currency || 'CHF' }).format(price);
}

const statusActions: Record<string, { label: string; icon: string; severity: string; action: () => void }[]> = {
    [PropertyStatus.DRAFT]: [
        { label: 'Submit for Approval', icon: 'pi pi-send', severity: 'info', action: submitForApproval },
        { label: 'Archive', icon: 'pi pi-inbox', severity: 'secondary', action: archive },
    ],
    [PropertyStatus.PENDING_APPROVAL]: [
        { label: 'Approve', icon: 'pi pi-check', severity: 'success', action: approve },
        { label: 'Reject', icon: 'pi pi-times', severity: 'danger', action: openRejectDialog },
    ],
    [PropertyStatus.APPROVED]: [
        { label: 'Publish', icon: 'pi pi-globe', severity: 'success', action: publish },
        { label: 'Archive', icon: 'pi pi-inbox', severity: 'secondary', action: archive },
    ],
    [PropertyStatus.REJECTED]: [
        { label: 'Back to Draft', icon: 'pi pi-refresh', severity: 'info', action: submitForApproval },
        { label: 'Archive', icon: 'pi pi-inbox', severity: 'secondary', action: archive },
    ],
    [PropertyStatus.PUBLISHED]: [
        { label: 'Archive', icon: 'pi pi-inbox', severity: 'secondary', action: archive },
    ],
    [PropertyStatus.ARCHIVED]: [],
};

const availableActions = statusActions[props.property.status] ?? [];
</script>

<template>
    <PageHeader :title="property.title || property.external_id" description="Property details">
        <template #actions>
            <div class="flex items-center gap-2">
                <Button
                    v-for="action in availableActions"
                    :key="action.label"
                    :label="action.label"
                    :icon="action.icon"
                    :severity="action.severity as any"
                    size="small"
                    @click="action.action"
                />
                <Link v-if="can('properties:update')" :href="route('admin.properties.edit', property.id)">
                    <Button label="Edit" icon="pi pi-pencil" severity="warn" size="small" />
                </Link>
                <Button
                    v-if="can('properties:delete')"
                    label="Delete"
                    icon="pi pi-trash"
                    severity="danger"
                    size="small"
                    @click="deleteProperty"
                />
            </div>
        </template>
    </PageHeader>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Main Info -->
        <div class="space-y-6 lg:col-span-2">
            <!-- Basic Details -->
            <div class="rounded-lg border border-gray-200 bg-white p-6">
                <h3 class="mb-4 text-lg font-semibold">Details</h3>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <span class="text-sm text-gray-500">Status</span>
                        <div class="mt-1"><StatusBadge :status="property.status" type="property" /></div>
                    </div>
                    <div>
                        <span class="text-sm text-gray-500">Transaction Type</span>
                        <div class="mt-1">
                            <Tag :value="property.transaction_type === 'rent' ? 'Rent' : 'Buy'" :severity="property.transaction_type === 'rent' ? 'info' : 'success'" />
                        </div>
                    </div>
                    <div>
                        <span class="text-sm text-gray-500">Price</span>
                        <p class="mt-1 text-lg font-semibold">
                            {{ formatPrice(property.price, property.currency) }}
                            <span v-if="property.transaction_type === 'rent'" class="text-sm font-normal text-gray-500">/mo</span>
                        </p>
                    </div>
                    <div v-if="property.additional_costs">
                        <span class="text-sm text-gray-500">Additional Costs</span>
                        <p class="mt-1">{{ formatPrice(property.additional_costs, property.currency) }}</p>
                    </div>
                    <div>
                        <span class="text-sm text-gray-500">Category</span>
                        <p class="mt-1">{{ property.category?.name ?? '–' }}</p>
                    </div>
                    <div>
                        <span class="text-sm text-gray-500">Rooms</span>
                        <p class="mt-1">{{ property.rooms ?? '–' }}</p>
                    </div>
                    <div>
                        <span class="text-sm text-gray-500">Surface</span>
                        <p class="mt-1">{{ property.surface ? `${property.surface} m²` : '–' }}</p>
                    </div>
                    <div>
                        <span class="text-sm text-gray-500">External ID</span>
                        <p class="mt-1 font-mono text-sm">{{ property.external_id }}</p>
                    </div>
                </div>
            </div>

            <!-- Location -->
            <div class="rounded-lg border border-gray-200 bg-white p-6">
                <h3 class="mb-4 text-lg font-semibold">Location</h3>
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <span class="text-sm text-gray-500">Address</span>
                        <p class="mt-1">{{ property.address }}</p>
                    </div>
                    <div>
                        <span class="text-sm text-gray-500">City</span>
                        <p class="mt-1">{{ property.city?.name ?? '–' }}</p>
                    </div>
                    <div>
                        <span class="text-sm text-gray-500">Canton</span>
                        <p class="mt-1">{{ property.canton?.code ?? '–' }} — {{ property.canton?.name }}</p>
                    </div>
                    <div>
                        <span class="text-sm text-gray-500">Postal Code</span>
                        <p class="mt-1">{{ property.postal_code ?? '–' }}</p>
                    </div>
                </div>
            </div>

            <!-- Description -->
            <div v-if="property.description" class="rounded-lg border border-gray-200 bg-white p-6">
                <h3 class="mb-4 text-lg font-semibold">Description</h3>
                <p class="whitespace-pre-line text-gray-700">{{ property.description }}</p>
            </div>

            <!-- Images -->
            <div class="rounded-lg border border-gray-200 bg-white p-6">
                <h3 class="mb-4 text-lg font-semibold">Images ({{ images.length }})</h3>
                <div v-if="images.length" class="grid grid-cols-2 gap-4 md:grid-cols-3">
                    <div v-for="img in images" :key="img.id" class="relative">
                        <Image :src="img.thumbnail_url || img.url" :alt="img.alt_text || ''" preview class="h-40 w-full rounded-lg object-cover" imageClass="h-40 w-full rounded-lg object-cover" />
                        <Tag v-if="img.is_primary" value="Primary" severity="info" class="absolute left-2 top-2" />
                    </div>
                </div>
                <p v-else class="text-gray-500">No images uploaded.</p>
            </div>

            <!-- Amenities -->
            <div v-if="property.amenities?.length" class="rounded-lg border border-gray-200 bg-white p-6">
                <h3 class="mb-4 text-lg font-semibold">Amenities</h3>
                <div class="flex flex-wrap gap-2">
                    <Tag
                        v-for="amenity in property.amenities"
                        :key="amenity.id"
                        :value="amenity.name"
                        :icon="amenity.icon ?? undefined"
                        severity="secondary"
                    />
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Owner / Agency -->
            <div class="rounded-lg border border-gray-200 bg-white p-6">
                <h3 class="mb-4 text-lg font-semibold">Ownership</h3>
                <div class="space-y-3">
                    <div v-if="property.owner">
                        <span class="text-sm text-gray-500">Owner</span>
                        <p class="mt-1">{{ property.owner.first_name }} {{ property.owner.last_name }}</p>
                    </div>
                    <div v-if="property.agency">
                        <span class="text-sm text-gray-500">Agency</span>
                        <p class="mt-1">{{ property.agency.name }}</p>
                    </div>
                </div>
            </div>

            <!-- Review Info -->
            <div v-if="property.reviewed_by || property.rejection_reason" class="rounded-lg border border-gray-200 bg-white p-6">
                <h3 class="mb-4 text-lg font-semibold">Review</h3>
                <div class="space-y-3">
                    <div v-if="property.reviewer">
                        <span class="text-sm text-gray-500">Reviewed By</span>
                        <p class="mt-1">{{ property.reviewer.first_name }} {{ property.reviewer.last_name }}</p>
                    </div>
                    <div v-if="property.reviewed_at">
                        <span class="text-sm text-gray-500">Reviewed At</span>
                        <p class="mt-1">{{ new Date(property.reviewed_at).toLocaleDateString() }}</p>
                    </div>
                    <div v-if="property.rejection_reason">
                        <span class="text-sm text-gray-500">Rejection Reason</span>
                        <p class="mt-1 rounded bg-red-50 p-2 text-sm text-red-700">{{ property.rejection_reason }}</p>
                    </div>
                </div>
            </div>

            <!-- Timestamps -->
            <div class="rounded-lg border border-gray-200 bg-white p-6">
                <h3 class="mb-4 text-lg font-semibold">Dates</h3>
                <div class="space-y-3">
                    <div>
                        <span class="text-sm text-gray-500">Created</span>
                        <p class="mt-1">{{ new Date(property.created_at).toLocaleDateString() }}</p>
                    </div>
                    <div>
                        <span class="text-sm text-gray-500">Updated</span>
                        <p class="mt-1">{{ new Date(property.updated_at).toLocaleDateString() }}</p>
                    </div>
                    <div v-if="property.published_at">
                        <span class="text-sm text-gray-500">Published</span>
                        <p class="mt-1">{{ new Date(property.published_at).toLocaleDateString() }}</p>
                    </div>
                </div>
            </div>

            <!-- External Link -->
            <div v-if="property.external_url" class="rounded-lg border border-gray-200 bg-white p-6">
                <h3 class="mb-4 text-lg font-semibold">External</h3>
                <a :href="property.external_url" target="_blank" rel="noopener noreferrer" class="text-blue-600 hover:underline">
                    <i class="pi pi-external-link mr-1" /> View original listing
                </a>
            </div>
        </div>
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
