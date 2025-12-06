<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PageHeader from '@/Components/Shared/PageHeader.vue';
import StatusBadge from '@/Components/Shared/StatusBadge.vue';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import type { User } from '@/Types/models';
import { usePermissions } from '@/Composables/usePermissions';
import { useConfirm } from '@/Composables/useConfirm';

defineOptions({ layout: AdminLayout });

interface Props {
    user: User;
}

const props = defineProps<Props>();
const { can } = usePermissions();
const { confirmDelete } = useConfirm();

function activateUser() {
    router.post(route('admin.users.activate', props.user.id));
}

function suspendUser() {
    router.post(route('admin.users.suspend', props.user.id));
}

function deleteUser() {
    confirmDelete(() => {
        router.delete(route('admin.users.destroy', props.user.id));
    });
}

function formatDate(date: string | null) {
    if (!date) return '–';
    return new Date(date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}
</script>

<template>
    <PageHeader :title="user.full_name" description="User details">
        <template #actions>
            <Button
                v-if="can('users:manage') && user.status !== 'active'"
                label="Activate"
                icon="pi pi-check"
                severity="success"
                @click="activateUser"
            />
            <Button
                v-if="can('users:manage') && user.status !== 'suspended'"
                label="Suspend"
                icon="pi pi-ban"
                severity="warn"
                @click="suspendUser"
            />
            <Button
                v-if="can('users:delete')"
                label="Delete"
                icon="pi pi-trash"
                severity="danger"
                @click="deleteUser"
            />
        </template>
    </PageHeader>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Main Info -->
        <div class="lg:col-span-2 space-y-6">
            <div class="rounded-lg border border-gray-200 bg-white p-6">
                <h3 class="mb-4 text-lg font-semibold text-gray-900">Personal Information</h3>
                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">First Name</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ user.first_name }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Last Name</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ user.last_name }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Email</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ user.email }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Phone</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ user.phone ?? '–' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Language</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ user.preferred_language.toUpperCase() }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Email Verified</dt>
                        <dd class="mt-1">
                            <Tag :value="user.email_verified_at ? 'Verified' : 'Not Verified'" :severity="user.email_verified_at ? 'success' : 'warn'" />
                        </dd>
                    </div>
                </dl>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-6">
                <h3 class="mb-4 text-lg font-semibold text-gray-900">Account Details</h3>
                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">User Type</dt>
                        <dd class="mt-1">
                            <Tag :value="(user.user_type ?? '').replace(/_/g, ' ')" severity="info" />
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Roles</dt>
                        <dd class="mt-1 flex flex-wrap gap-1">
                            <Tag v-for="role in user.roles" :key="role.id" :value="role.name" severity="secondary" />
                            <span v-if="!user.roles?.length" class="text-sm text-gray-400">No roles assigned</span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Agency</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ user.agency?.name ?? '–' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Last Login</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ formatDate(user.last_login_at) }}</dd>
                    </div>
                </dl>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <div class="rounded-lg border border-gray-200 bg-white p-6">
                <h3 class="mb-4 text-lg font-semibold text-gray-900">Status</h3>
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500">Account Status</span>
                        <StatusBadge :status="user.status" type="account" />
                    </div>
                </div>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-6">
                <h3 class="mb-4 text-lg font-semibold text-gray-900">Dates</h3>
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500">Joined</span>
                        <span class="text-sm text-gray-900">{{ formatDate(user.created_at) }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500">Updated</span>
                        <span class="text-sm text-gray-900">{{ formatDate(user.updated_at) }}</span>
                    </div>
                    <div v-if="user.email_verified_at" class="flex items-center justify-between">
                        <span class="text-sm text-gray-500">Verified</span>
                        <span class="text-sm text-gray-900">{{ formatDate(user.email_verified_at) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
