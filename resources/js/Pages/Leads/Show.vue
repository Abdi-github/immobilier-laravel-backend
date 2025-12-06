<script setup lang="ts">
import { ref } from 'vue';
import { router, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PageHeader from '@/Components/Shared/PageHeader.vue';
import StatusBadge from '@/Components/Shared/StatusBadge.vue';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Select from 'primevue/select';
import Textarea from 'primevue/textarea';
import Dialog from 'primevue/dialog';
import DatePicker from 'primevue/datepicker';
import type { Lead, User } from '@/Types/models';
import { useConfirm } from '@/Composables/useConfirm';
import { usePermissions } from '@/Composables/usePermissions';

defineOptions({ layout: AdminLayout });

interface Props {
    lead: Lead;
    availableTransitions: string[];
    agents: Pick<User, 'id' | 'first_name' | 'last_name'>[];
}

const props = defineProps<Props>();

const { can } = usePermissions();
const { confirmDelete } = useConfirm();

// Status transition
const showStatusModal = ref(false);
const statusForm = useForm({
    status: '' as string,
    close_reason: '',
});

function openStatusModal(status: string) {
    statusForm.status = status;
    statusForm.close_reason = '';
    statusForm.clearErrors();
    showStatusModal.value = true;
}

function submitStatus() {
    statusForm.post(route('admin.leads.status', props.lead.id), {
        onSuccess: () => { showStatusModal.value = false; },
    });
}

// Assignment
const showAssignModal = ref(false);
const assignForm = useForm({ assigned_to: null as number | null });

function openAssignModal() {
    assignForm.assigned_to = props.lead.assigned_to;
    assignForm.clearErrors();
    showAssignModal.value = true;
}

function submitAssign() {
    assignForm.post(route('admin.leads.assign', props.lead.id), {
        onSuccess: () => { showAssignModal.value = false; },
    });
}

// Notes
const noteForm = useForm({
    content: '',
    is_internal: true,
});

function submitNote() {
    noteForm.post(route('admin.leads.notes.store', props.lead.id), {
        preserveScroll: true,
        onSuccess: () => { noteForm.reset(); },
    });
}

// Update lead fields
const editForm = useForm({
    priority: props.lead.priority,
    follow_up_date: props.lead.follow_up_date ?? '',
    viewing_scheduled_at: props.lead.viewing_scheduled_at ?? '',
});

function submitEdit() {
    editForm.patch(route('admin.leads.update', props.lead.id), {
        preserveScroll: true,
    });
}

// Delete
function deleteLead() {
    confirmDelete(() => {
        router.delete(route('admin.leads.destroy', props.lead.id));
    });
}

function prioritySeverity(priority: string): 'success' | 'info' | 'warn' | 'danger' | 'secondary' {
    const map: Record<string, 'success' | 'info' | 'warn' | 'danger'> = {
        low: 'info', medium: 'warn', high: 'danger', urgent: 'danger',
    };
    return map[priority] ?? 'secondary';
}

function formatDate(iso: string | null): string {
    if (!iso) return '–';
    return new Date(iso).toLocaleDateString('en-CH', { year: 'numeric', month: 'short', day: 'numeric' });
}

function formatDateTime(iso: string | null): string {
    if (!iso) return '–';
    return new Date(iso).toLocaleString('en-CH', {
        year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit',
    });
}

const statusLabels: Record<string, string> = {
    NEW: 'New',
    CONTACTED: 'Contacted',
    QUALIFIED: 'Qualified',
    VIEWING_SCHEDULED: 'Viewing Scheduled',
    NEGOTIATING: 'Negotiating',
    WON: 'Won',
    LOST: 'Lost',
    ARCHIVED: 'Archived',
};
</script>

<template>
    <PageHeader
        :title="`${lead.contact_first_name} ${lead.contact_last_name}`"
        description="Lead details and management"
    >
        <template #actions>
            <Link :href="route('admin.leads.index')">
                <Button label="Back to Leads" icon="pi pi-arrow-left" severity="secondary" outlined />
            </Link>
            <Button
                v-if="can('leads:delete')"
                label="Delete"
                icon="pi pi-trash"
                severity="danger"
                outlined
                @click="deleteLead"
            />
        </template>
    </PageHeader>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Main Content -->
        <div class="space-y-6 lg:col-span-2">
            <!-- Contact Info -->
            <div class="rounded-lg border border-gray-200 bg-white p-6">
                <h3 class="mb-4 text-lg font-semibold">Contact Information</h3>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <span class="text-sm text-gray-500">Name</span>
                        <p class="font-medium">{{ lead.contact_first_name }} {{ lead.contact_last_name }}</p>
                    </div>
                    <div>
                        <span class="text-sm text-gray-500">Email</span>
                        <p class="font-medium">
                            <a :href="`mailto:${lead.contact_email}`" class="text-blue-600 hover:underline">{{ lead.contact_email }}</a>
                        </p>
                    </div>
                    <div>
                        <span class="text-sm text-gray-500">Phone</span>
                        <p class="font-medium">{{ lead.contact_phone ?? '–' }}</p>
                    </div>
                    <div>
                        <span class="text-sm text-gray-500">Preferred Contact</span>
                        <p class="font-medium capitalize">{{ lead.preferred_contact_method ?? '–' }}</p>
                    </div>
                    <div>
                        <span class="text-sm text-gray-500">Language</span>
                        <p class="font-medium uppercase">{{ lead.preferred_language }}</p>
                    </div>
                    <div>
                        <span class="text-sm text-gray-500">Inquiry Type</span>
                        <p class="font-medium capitalize">{{ lead.inquiry_type?.replace(/_/g, ' ') ?? '–' }}</p>
                    </div>
                </div>
            </div>

            <!-- Message -->
            <div class="rounded-lg border border-gray-200 bg-white p-6">
                <h3 class="mb-4 text-lg font-semibold">Message</h3>
                <p class="whitespace-pre-wrap text-gray-700">{{ lead.message ?? 'No message provided.' }}</p>
            </div>

            <!-- Property -->
            <div v-if="lead.property" class="rounded-lg border border-gray-200 bg-white p-6">
                <h3 class="mb-4 text-lg font-semibold">Related Property</h3>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <span class="text-sm text-gray-500">Address</span>
                        <p>
                            <Link :href="route('admin.properties.show', lead.property.id)" class="font-medium text-blue-600 hover:underline">
                                {{ lead.property.address }}
                            </Link>
                        </p>
                    </div>
                    <div>
                        <span class="text-sm text-gray-500">Price</span>
                        <p class="font-medium">{{ lead.property.currency }} {{ Number(lead.property.price).toLocaleString() }}</p>
                    </div>
                    <div>
                        <span class="text-sm text-gray-500">Category</span>
                        <p class="font-medium">{{ lead.property.category?.name ?? '–' }}</p>
                    </div>
                    <div>
                        <span class="text-sm text-gray-500">Transaction</span>
                        <p class="font-medium capitalize">{{ lead.property.transaction_type }}</p>
                    </div>
                    <div>
                        <span class="text-sm text-gray-500">Location</span>
                        <p class="font-medium">{{ lead.property.city?.name ?? '' }}, {{ lead.property.canton?.code ?? '' }}</p>
                    </div>
                    <div>
                        <span class="text-sm text-gray-500">Status</span>
                        <StatusBadge :status="lead.property.status" type="property" />
                    </div>
                </div>
            </div>

            <!-- Notes -->
            <div class="rounded-lg border border-gray-200 bg-white p-6">
                <h3 class="mb-4 text-lg font-semibold">Notes</h3>

                <!-- Add Note Form -->
                <form v-if="can('leads:update')" @submit.prevent="submitNote" class="mb-6">
                    <Textarea v-model="noteForm.content" rows="3" placeholder="Add a note..." class="w-full" :invalid="!!noteForm.errors.content" />
                    <small v-if="noteForm.errors.content" class="text-red-500">{{ noteForm.errors.content }}</small>
                    <div class="mt-2 flex items-center justify-between">
                        <label class="flex items-center gap-2 text-sm text-gray-600">
                            <input type="checkbox" v-model="noteForm.is_internal" class="rounded" />
                            Internal note
                        </label>
                        <Button type="submit" label="Add Note" icon="pi pi-plus" size="small" :loading="noteForm.processing" :disabled="!noteForm.content.trim()" />
                    </div>
                </form>

                <!-- Notes Timeline -->
                <div v-if="lead.notes && lead.notes.length > 0" class="space-y-4">
                    <div v-for="note in lead.notes" :key="note.id" class="border-l-4 pl-4" :class="note.is_internal ? 'border-yellow-400' : 'border-blue-400'">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-medium">
                                    {{ note.creator ? `${note.creator.first_name} ${note.creator.last_name}` : 'Unknown' }}
                                </span>
                                <Tag v-if="note.is_internal" value="Internal" severity="warn" class="text-xs" />
                            </div>
                            <span class="text-xs text-gray-400">{{ formatDateTime(note.created_at) }}</span>
                        </div>
                        <p class="mt-1 whitespace-pre-wrap text-sm text-gray-700">{{ note.content }}</p>
                    </div>
                </div>
                <p v-else class="text-gray-400">No notes yet.</p>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Status & Actions -->
            <div class="rounded-lg border border-gray-200 bg-white p-6">
                <h3 class="mb-4 text-lg font-semibold">Status</h3>

                <div class="mb-4">
                    <StatusBadge :status="lead.status" type="lead" />
                </div>

                <!-- Status Transitions -->
                <div v-if="can('leads:update') && availableTransitions.length > 0" class="space-y-2">
                    <p class="text-xs font-medium text-gray-500 uppercase">Transition to:</p>
                    <div class="flex flex-wrap gap-2">
                        <Button
                            v-for="status in availableTransitions"
                            :key="status"
                            :label="statusLabels[status] ?? status"
                            size="small"
                            :severity="['WON'].includes(status) ? 'success' : ['LOST', 'ARCHIVED'].includes(status) ? 'danger' : 'info'"
                            outlined
                            @click="openStatusModal(status)"
                        />
                    </div>
                </div>
            </div>

            <!-- Assignment -->
            <div class="rounded-lg border border-gray-200 bg-white p-6">
                <h3 class="mb-4 text-lg font-semibold">Assignment</h3>
                <div class="mb-3">
                    <span class="text-sm text-gray-500">Assigned To</span>
                    <p class="font-medium">
                        <template v-if="lead.assigned_user">
                            {{ lead.assigned_user.first_name }} {{ lead.assigned_user.last_name }}
                        </template>
                        <Tag v-else value="Unassigned" severity="warn" />
                    </p>
                </div>
                <Button
                    v-if="can('leads:manage')"
                    :label="lead.assigned_user ? 'Reassign' : 'Assign'"
                    icon="pi pi-user"
                    size="small"
                    outlined
                    @click="openAssignModal"
                />
            </div>

            <!-- Lead Details -->
            <div class="rounded-lg border border-gray-200 bg-white p-6">
                <h3 class="mb-4 text-lg font-semibold">Details</h3>
                <div class="space-y-3">
                    <div>
                        <span class="text-sm text-gray-500">Priority</span>
                        <div><Tag :value="lead.priority" :severity="prioritySeverity(lead.priority)" /></div>
                    </div>
                    <div>
                        <span class="text-sm text-gray-500">Source</span>
                        <p class="font-medium capitalize">{{ lead.source?.replace(/_/g, ' ') ?? '–' }}</p>
                    </div>
                    <div>
                        <span class="text-sm text-gray-500">Agency</span>
                        <p class="font-medium">{{ lead.agency?.name ?? '–' }}</p>
                    </div>
                    <div>
                        <span class="text-sm text-gray-500">Follow-up Date</span>
                        <p class="font-medium">{{ formatDate(lead.follow_up_date) }}</p>
                    </div>
                    <div>
                        <span class="text-sm text-gray-500">Viewing Scheduled</span>
                        <p class="font-medium">{{ formatDateTime(lead.viewing_scheduled_at) }}</p>
                    </div>
                    <div v-if="lead.first_response_at">
                        <span class="text-sm text-gray-500">First Response</span>
                        <p class="font-medium">{{ formatDateTime(lead.first_response_at) }}</p>
                    </div>
                    <div v-if="lead.closed_at">
                        <span class="text-sm text-gray-500">Closed At</span>
                        <p class="font-medium">{{ formatDateTime(lead.closed_at) }}</p>
                    </div>
                    <div v-if="lead.close_reason">
                        <span class="text-sm text-gray-500">Close Reason</span>
                        <p class="text-sm">{{ lead.close_reason }}</p>
                    </div>
                </div>
            </div>

            <!-- Dates -->
            <div class="rounded-lg border border-gray-200 bg-white p-6">
                <h3 class="mb-4 text-lg font-semibold">Dates</h3>
                <div class="space-y-3">
                    <div>
                        <span class="text-sm text-gray-500">Created</span>
                        <p class="font-medium">{{ formatDateTime(lead.created_at) }}</p>
                    </div>
                    <div>
                        <span class="text-sm text-gray-500">Updated</span>
                        <p class="font-medium">{{ formatDateTime(lead.updated_at) }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Transition Modal -->
    <Dialog
        v-model:visible="showStatusModal"
        header="Change Lead Status"
        modal
        :style="{ width: '450px' }"
        data-testid="lead-status-modal"
    >
        <form @submit.prevent="submitStatus">
            <p class="mb-4 text-sm text-gray-600">
                Change status from <strong>{{ statusLabels[lead.status] ?? lead.status }}</strong>
                to <strong>{{ statusLabels[statusForm.status] ?? statusForm.status }}</strong>?
            </p>

            <div v-if="['WON', 'LOST'].includes(statusForm.status)" class="mb-4">
                <label class="mb-1 block text-sm font-medium">Close Reason</label>
                <Textarea
                    v-model="statusForm.close_reason"
                    rows="3"
                    class="w-full"
                    :placeholder="statusForm.status === 'LOST' ? 'Why was this lead lost?' : 'Notes about the deal...'"
                />
            </div>

            <div class="flex justify-end gap-2">
                <Button label="Cancel" severity="secondary" text @click="showStatusModal = false" />
                <Button type="submit" label="Confirm" :loading="statusForm.processing" />
            </div>
        </form>
    </Dialog>

    <!-- Assign Modal -->
    <Dialog
        v-model:visible="showAssignModal"
        header="Assign Lead"
        modal
        :style="{ width: '400px' }"
        data-testid="lead-assign-modal"
    >
        <form @submit.prevent="submitAssign">
            <div class="mb-4">
                <label class="mb-1 block text-sm font-medium">Agent</label>
                <Select
                    v-model="assignForm.assigned_to"
                    :options="agents"
                    :optionLabel="(a: any) => `${a.first_name} ${a.last_name}`"
                    optionValue="id"
                    placeholder="Select an agent..."
                    class="w-full"
                    :invalid="!!assignForm.errors.assigned_to"
                />
                <small v-if="assignForm.errors.assigned_to" class="text-red-500">{{ assignForm.errors.assigned_to }}</small>
            </div>

            <div class="flex justify-end gap-2">
                <Button label="Cancel" severity="secondary" text @click="showAssignModal = false" />
                <Button type="submit" label="Assign" :loading="assignForm.processing" :disabled="!assignForm.assigned_to" />
            </div>
        </form>
    </Dialog>
</template>
