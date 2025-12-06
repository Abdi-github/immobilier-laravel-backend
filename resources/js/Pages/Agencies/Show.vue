<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PageHeader from '@/Components/Shared/PageHeader.vue';
import StatusBadge from '@/Components/Shared/StatusBadge.vue';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import type { Agency } from '@/Types/models';
import { usePermissions } from '@/Composables/usePermissions';
import { useConfirm } from '@/Composables/useConfirm';

defineOptions({ layout: AdminLayout });

interface Props {
    agency: Agency & {
        members_count: number;
        properties_count: number;
        leads_count: number;
    };
}

const props = defineProps<Props>();
const { can } = usePermissions();
const { confirmDelete } = useConfirm();

function toggleVerification() {
    if (props.agency.is_verified) {
        router.post(route('admin.agencies.unverify', props.agency.id));
    } else {
        router.post(route('admin.agencies.verify', props.agency.id));
    }
}

function updateStatus(status: string) {
    router.post(route('admin.agencies.status', props.agency.id), { status });
}

function deleteAgency() {
    confirmDelete(() => {
        router.delete(route('admin.agencies.destroy', props.agency.id));
    });
}

function formatDate(date: string | null) {
    if (!date) return '–';
    return new Date(date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}
</script>

<template>
    <PageHeader :title="agency.name" description="Agency details">
        <template #actions>
            <Button
                v-if="can('agencies:manage')"
                :label="agency.is_verified ? 'Unverify' : 'Verify'"
                :icon="agency.is_verified ? 'pi pi-times-circle' : 'pi pi-check-circle'"
                :severity="agency.is_verified ? 'warn' : 'success'"
                @click="toggleVerification"
            />
            <Button
                v-if="can('agencies:delete')"
                label="Delete"
                icon="pi pi-trash"
                severity="danger"
                @click="deleteAgency"
            />
        </template>
    </PageHeader>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Main Info -->
        <div class="lg:col-span-2 space-y-6">
            <div class="rounded-lg border border-gray-200 bg-white p-6">
                <h3 class="mb-4 text-lg font-semibold text-gray-900">Agency Information</h3>
                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Name</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ agency.name }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Slug</dt>
                        <dd class="mt-1 text-sm text-gray-500">{{ agency.slug }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Email</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ agency.email ?? '–' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Phone</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ agency.phone ?? '–' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Website</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            <a v-if="agency.website" :href="agency.website" target="_blank" rel="noopener noreferrer" class="text-blue-600 hover:underline">
                                {{ agency.website }}
                            </a>
                            <span v-else>–</span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Contact Person</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ agency.contact_person ?? '–' }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-sm font-medium text-gray-500">Address</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ agency.address ?? '–' }}</dd>
                    </div>
                </dl>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-6">
                <h3 class="mb-4 text-lg font-semibold text-gray-900">Location</h3>
                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Canton</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ agency.canton?.name ?? '–' }} ({{ agency.canton?.code ?? '–' }})</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">City</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ agency.city?.name ?? '–' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Postal Code</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ agency.postal_code ?? '–' }}</dd>
                    </div>
                </dl>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Status Card -->
            <div class="rounded-lg border border-gray-200 bg-white p-6">
                <h3 class="mb-4 text-lg font-semibold text-gray-900">Status</h3>
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500">Status</span>
                        <StatusBadge :status="agency.status" type="account" />
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500">Verification</span>
                        <Tag :value="agency.is_verified ? 'Verified' : 'Unverified'" :severity="agency.is_verified ? 'success' : 'warn'" />
                    </div>
                    <div v-if="agency.verification_date" class="flex items-center justify-between">
                        <span class="text-sm text-gray-500">Verified On</span>
                        <span class="text-sm text-gray-900">{{ formatDate(agency.verification_date) }}</span>
                    </div>

                    <div v-if="can('agencies:manage')" class="border-t pt-4 space-y-2">
                        <p class="text-xs font-medium text-gray-500 uppercase">Change Status</p>
                        <div class="flex flex-wrap gap-2">
                            <Button
                                v-if="agency.status !== 'active'"
                                label="Activate"
                                size="small"
                                severity="success"
                                @click="updateStatus('active')"
                            />
                            <Button
                                v-if="agency.status !== 'suspended'"
                                label="Suspend"
                                size="small"
                                severity="warn"
                                @click="updateStatus('suspended')"
                            />
                            <Button
                                v-if="agency.status !== 'inactive'"
                                label="Deactivate"
                                size="small"
                                severity="secondary"
                                @click="updateStatus('inactive')"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats Card -->
            <div class="rounded-lg border border-gray-200 bg-white p-6">
                <h3 class="mb-4 text-lg font-semibold text-gray-900">Statistics</h3>
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500">Properties</span>
                        <span class="text-sm font-semibold text-gray-900">{{ agency.properties_count }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500">Members</span>
                        <span class="text-sm font-semibold text-gray-900">{{ agency.members_count }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500">Leads</span>
                        <span class="text-sm font-semibold text-gray-900">{{ agency.leads_count }}</span>
                    </div>
                </div>
            </div>

            <!-- Dates Card -->
            <div class="rounded-lg border border-gray-200 bg-white p-6">
                <h3 class="mb-4 text-lg font-semibold text-gray-900">Dates</h3>
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500">Created</span>
                        <span class="text-sm text-gray-900">{{ formatDate(agency.created_at) }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500">Updated</span>
                        <span class="text-sm text-gray-900">{{ formatDate(agency.updated_at) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
