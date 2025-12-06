<script setup lang="ts">
import { ref } from 'vue';
import { router, useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PageHeader from '@/Components/Shared/PageHeader.vue';
import MultilingualInput from '@/Components/Shared/MultilingualInput.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import InputSwitch from 'primevue/inputswitch';
import Tag from 'primevue/tag';
import type { Role } from '@/Types/models';
import { usePermissions } from '@/Composables/usePermissions';
import { useConfirm } from '@/Composables/useConfirm';

defineOptions({ layout: AdminLayout });

interface RoleRow extends Role {
    is_system: boolean;
    is_active: boolean;
    permissions_count: number;
}

interface Props {
    roles: RoleRow[];
}

const props = defineProps<Props>();
const { can } = usePermissions();
const { confirmDelete } = useConfirm();

const showCreateModal = ref(false);
const createForm = useForm({
    name: '',
    display_name: { en: '', fr: '', de: '', it: '' } as Record<string, string>,
    description: { en: '', fr: '', de: '', it: '' } as Record<string, string>,
    is_active: true,
});

function openCreate() {
    createForm.reset();
    createForm.display_name = { en: '', fr: '', de: '', it: '' };
    createForm.description = { en: '', fr: '', de: '', it: '' };
    createForm.is_active = true;
    createForm.clearErrors();
    showCreateModal.value = true;
}

function submitCreate() {
    createForm.post(route('admin.roles.store'), {
        onSuccess: () => { showCreateModal.value = false; },
    });
}

function deleteRole(id: number) {
    confirmDelete(() => {
        router.delete(route('admin.roles.destroy', id));
    });
}
</script>

<template>
    <PageHeader title="Roles" description="Manage user roles and permissions">
        <template #actions>
            <Button v-if="can('roles:create')" label="Create Role" icon="pi pi-plus" @click="openCreate" />
        </template>
    </PageHeader>

    <div class="rounded-lg border border-gray-200 bg-white">
        <DataTable :value="roles" stripedRows responsiveLayout="scroll" data-testid="roles-table">
            <Column field="name" header="Name" style="min-width: 150px">
                <template #body="{ data }">
                    <Link :href="route('admin.roles.show', data.id)" class="font-medium text-blue-600 hover:underline">
                        {{ data.name }}
                    </Link>
                </template>
            </Column>

            <Column field="display_name" header="Display Name" style="min-width: 200px">
                <template #body="{ data }">
                    <span>{{ data.display_name?.en ?? '–' }}</span>
                </template>
            </Column>

            <Column field="permissions_count" header="Permissions" style="min-width: 100px">
                <template #body="{ data }">
                    <Tag :value="`${data.permissions_count}`" severity="info" />
                </template>
            </Column>

            <Column field="is_system" header="System" style="min-width: 80px">
                <template #body="{ data }">
                    <Tag v-if="data.is_system" value="System" severity="warn" />
                    <span v-else class="text-gray-400">–</span>
                </template>
            </Column>

            <Column field="is_active" header="Active" style="min-width: 80px">
                <template #body="{ data }">
                    <Tag :value="data.is_active ? 'Yes' : 'No'" :severity="data.is_active ? 'success' : 'danger'" />
                </template>
            </Column>

            <Column header="Actions" style="min-width: 100px" :exportable="false">
                <template #body="{ data }">
                    <div class="flex items-center gap-1">
                        <Link :href="route('admin.roles.show', data.id)">
                            <Button icon="pi pi-eye" severity="info" text rounded size="small" />
                        </Link>
                        <Button
                            v-if="can('roles:manage') && !data.is_system"
                            icon="pi pi-trash"
                            severity="danger"
                            text
                            rounded
                            size="small"
                            @click="deleteRole(data.id)"
                        />
                    </div>
                </template>
            </Column>

            <template #empty>
                <div class="py-8 text-center text-gray-500">No roles found.</div>
            </template>
        </DataTable>
    </div>

    <!-- Create Role Modal -->
    <Dialog
        v-model:visible="showCreateModal"
        header="Create Role"
        modal
        :style="{ width: '500px' }"
        data-testid="role-create-modal"
    >
        <form @submit.prevent="submitCreate" class="flex flex-col gap-4">
            <div>
                <label class="mb-1 block text-sm font-medium">Name *</label>
                <InputText
                    v-model="createForm.name"
                    class="w-full"
                    placeholder="e.g. content_editor"
                    :invalid="!!createForm.errors.name"
                />
                <small v-if="createForm.errors.name" class="text-red-500">{{ createForm.errors.name }}</small>
            </div>

            <MultilingualInput
                v-model="createForm.display_name"
                label="Display Name"
                :errors="createForm.errors"
            />

            <MultilingualInput
                v-model="createForm.description"
                label="Description"
                type="textarea"
                :errors="createForm.errors"
            />

            <div class="flex items-center gap-2">
                <InputSwitch v-model="createForm.is_active" />
                <span class="text-sm">Active</span>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <Button label="Cancel" severity="secondary" text @click="showCreateModal = false" />
                <Button type="submit" label="Create" :loading="createForm.processing" />
            </div>
        </form>
    </Dialog>
</template>
