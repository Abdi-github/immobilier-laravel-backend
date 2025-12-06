<script setup lang="ts">
import { ref, computed } from 'vue';
import { router, useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PageHeader from '@/Components/Shared/PageHeader.vue';
import MultilingualInput from '@/Components/Shared/MultilingualInput.vue';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import InputSwitch from 'primevue/inputswitch';
import Tag from 'primevue/tag';
import Checkbox from 'primevue/checkbox';
import type { Role, Permission } from '@/Types/models';
import { usePermissions } from '@/Composables/usePermissions';

defineOptions({ layout: AdminLayout });

interface PermissionItem {
    id: number;
    name: string;
    display_name: Record<string, string> | null;
    description: Record<string, string> | null;
    resource: string;
    action: string;
}

interface Props {
    role: Role & { is_system: boolean; is_active: boolean; permissions: PermissionItem[] };
    allPermissions: PermissionItem[];
    groupedPermissions: Record<string, PermissionItem[]>;
}

const props = defineProps<Props>();
const { can } = usePermissions();

const rolePermissionIds = computed(() => new Set(props.role.permissions?.map(p => p.id) ?? []));

// Edit role modal
const showEditModal = ref(false);
const editForm = useForm({
    name: props.role.name,
    display_name: { en: '', fr: '', de: '', it: '', ...props.role.display_name } as Record<string, string>,
    description: { en: '', fr: '', de: '', it: '', ...props.role.description } as Record<string, string>,
    is_active: props.role.is_active,
});

function openEdit() {
    editForm.name = props.role.name;
    editForm.display_name = { en: '', fr: '', de: '', it: '', ...props.role.display_name };
    editForm.description = { en: '', fr: '', de: '', it: '', ...props.role.description };
    editForm.is_active = props.role.is_active;
    editForm.clearErrors();
    showEditModal.value = true;
}

function submitEdit() {
    editForm.put(route('admin.roles.update', props.role.id), {
        onSuccess: () => { showEditModal.value = false; },
    });
}

// Permission matrix
const selectedPermissionIds = ref<number[]>(props.role.permissions?.map(p => p.id) ?? []);

function isGroupAllSelected(perms: PermissionItem[]): boolean {
    return perms.every(p => selectedPermissionIds.value.includes(p.id));
}

function toggleGroup(perms: PermissionItem[]) {
    if (isGroupAllSelected(perms)) {
        const ids = new Set(perms.map(p => p.id));
        selectedPermissionIds.value = selectedPermissionIds.value.filter(id => !ids.has(id));
    } else {
        const existing = new Set(selectedPermissionIds.value);
        for (const p of perms) existing.add(p.id);
        selectedPermissionIds.value = [...existing];
    }
}

const permissionsChanged = computed(() => {
    const current = new Set(props.role.permissions?.map(p => p.id) ?? []);
    if (current.size !== selectedPermissionIds.value.length) return true;
    return selectedPermissionIds.value.some(id => !current.has(id));
});

function savePermissions() {
    router.put(route('admin.roles.permissions.sync', props.role.id), {
        permissions: selectedPermissionIds.value,
    }, { preserveScroll: true });
}
</script>

<template>
    <PageHeader :title="`Role: ${role.name}`" description="View and manage role permissions">
        <template #actions>
            <Link :href="route('admin.roles.index')">
                <Button label="Back" icon="pi pi-arrow-left" severity="secondary" text />
            </Link>
            <Button v-if="can('roles:manage')" label="Edit Role" icon="pi pi-pencil" severity="warn" @click="openEdit" />
        </template>
    </PageHeader>

    <!-- Role Info -->
    <div class="mb-6 rounded-lg border border-gray-200 bg-white p-6">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div>
                <span class="text-sm text-gray-500">Name</span>
                <p class="font-medium">{{ role.name }}</p>
            </div>
            <div>
                <span class="text-sm text-gray-500">Display Name</span>
                <p class="font-medium">{{ role.display_name?.en ?? '–' }}</p>
            </div>
            <div class="flex gap-4">
                <div>
                    <span class="text-sm text-gray-500">System</span>
                    <p><Tag v-if="role.is_system" value="System" severity="warn" /><span v-else>No</span></p>
                </div>
                <div>
                    <span class="text-sm text-gray-500">Active</span>
                    <p><Tag :value="role.is_active ? 'Yes' : 'No'" :severity="role.is_active ? 'success' : 'danger'" /></p>
                </div>
                <div>
                    <span class="text-sm text-gray-500">Permissions</span>
                    <p><Tag :value="`${role.permissions?.length ?? 0}`" severity="info" /></p>
                </div>
            </div>
        </div>
        <div v-if="role.description?.en" class="mt-3">
            <span class="text-sm text-gray-500">Description</span>
            <p class="text-gray-700">{{ role.description.en }}</p>
        </div>
    </div>

    <!-- Permission Matrix -->
    <div class="rounded-lg border border-gray-200 bg-white p-6" data-testid="permission-matrix">
        <div class="mb-4 flex items-center justify-between">
            <h3 class="text-lg font-semibold">Permissions</h3>
            <Button
                v-if="can('roles:manage') && permissionsChanged"
                label="Save Permissions"
                icon="pi pi-save"
                @click="savePermissions"
            />
        </div>

        <div class="space-y-4">
            <div v-for="(perms, resource) in groupedPermissions" :key="resource" class="rounded border border-gray-100 p-3">
                <div class="mb-2 flex items-center gap-2">
                    <Checkbox
                        v-if="can('roles:manage')"
                        :modelValue="isGroupAllSelected(perms)"
                        binary
                        @update:modelValue="toggleGroup(perms)"
                    />
                    <span class="font-semibold capitalize">{{ resource }}</span>
                    <span class="text-xs text-gray-400">({{ perms.length }})</span>
                </div>
                <div class="ml-6 flex flex-wrap gap-3">
                    <label
                        v-for="perm in perms"
                        :key="perm.id"
                        class="flex items-center gap-1.5 text-sm"
                    >
                        <Checkbox
                            v-model="selectedPermissionIds"
                            :value="perm.id"
                            :disabled="!can('roles:manage')"
                        />
                        <span>{{ perm.action }}</span>
                    </label>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <Dialog
        v-model:visible="showEditModal"
        header="Edit Role"
        modal
        :style="{ width: '500px' }"
        data-testid="role-edit-modal"
    >
        <form @submit.prevent="submitEdit" class="flex flex-col gap-4">
            <div v-if="!role.is_system">
                <label class="mb-1 block text-sm font-medium">Name</label>
                <InputText v-model="editForm.name" class="w-full" :invalid="!!editForm.errors.name" />
                <small v-if="editForm.errors.name" class="text-red-500">{{ editForm.errors.name }}</small>
            </div>

            <MultilingualInput
                v-model="editForm.display_name"
                label="Display Name"
                :errors="editForm.errors"
            />

            <MultilingualInput
                v-model="editForm.description"
                label="Description"
                type="textarea"
                :errors="editForm.errors"
            />

            <div class="flex items-center gap-2">
                <InputSwitch v-model="editForm.is_active" />
                <span class="text-sm">Active</span>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <Button label="Cancel" severity="secondary" text @click="showEditModal = false" />
                <Button type="submit" label="Update" :loading="editForm.processing" />
            </div>
        </form>
    </Dialog>
</template>
