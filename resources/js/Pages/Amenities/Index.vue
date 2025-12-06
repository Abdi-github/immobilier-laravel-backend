<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PageHeader from '@/Components/Shared/PageHeader.vue';
import MultilingualInput from '@/Components/Shared/MultilingualInput.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Select from 'primevue/select';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import InputSwitch from 'primevue/inputswitch';
import Tag from 'primevue/tag';
import type { Amenity } from '@/Types/models';
import { useConfirm } from '@/Composables/useConfirm';
import { usePermissions } from '@/Composables/usePermissions';

defineOptions({ layout: AdminLayout });

interface Props {
    amenities: Amenity[];
    groups: string[];
    filters: Record<string, string>;
}

const props = defineProps<Props>();

const { can } = usePermissions();
const { confirmDelete } = useConfirm();

// Filter
const groupFilter = ref<string | null>(props.filters.group ?? null);

function reload() {
    router.get(route('admin.amenities.index'), {
        group: groupFilter.value || undefined,
    }, { preserveState: true, preserveScroll: true });
}

watch(groupFilter, () => reload());

function clearFilters() {
    groupFilter.value = null;
    reload();
}

const groupOptions = computed(() =>
    props.groups.map(g => ({ label: g.charAt(0).toUpperCase() + g.slice(1), value: g }))
);

// Modal
const showFormModal = ref(false);
const editingAmenity = ref<Amenity | null>(null);

const form = useForm({
    group: '' as string,
    name: { en: '', fr: '', de: '', it: '' } as Record<string, string>,
    icon: '',
    sort_order: 0,
    is_active: true,
});

function openCreateModal() {
    editingAmenity.value = null;
    form.reset();
    form.is_active = true;
    form.name = { en: '', fr: '', de: '', it: '' };
    form.clearErrors();
    showFormModal.value = true;
}

function openEditModal(amenity: Amenity) {
    editingAmenity.value = amenity;
    form.group = amenity.group;
    form.name = { en: '', fr: '', de: '', it: '', ...amenity.name };
    form.icon = amenity.icon ?? '';
    form.sort_order = amenity.sort_order;
    form.is_active = amenity.is_active;
    form.clearErrors();
    showFormModal.value = true;
}

function submitForm() {
    if (editingAmenity.value) {
        form.put(route('admin.amenities.update', editingAmenity.value.id), {
            onSuccess: () => { showFormModal.value = false; },
        });
    } else {
        form.post(route('admin.amenities.store'), {
            onSuccess: () => { showFormModal.value = false; },
        });
    }
}

function deleteAmenity(id: number) {
    confirmDelete(() => {
        router.delete(route('admin.amenities.destroy', id));
    });
}

function groupSeverity(group: string): 'success' | 'info' | 'warn' | 'danger' | 'secondary' {
    const map: Record<string, 'success' | 'info' | 'warn' | 'danger'> = {
        general: 'info',
        kitchen: 'success',
        bathroom: 'info',
        outdoor: 'success',
        security: 'danger',
        parking: 'warn',
        accessibility: 'warn',
        energy: 'success',
    };
    return map[group] ?? 'secondary';
}
</script>

<template>
    <PageHeader title="Amenities" description="Manage property amenities">
        <template #actions>
            <Button v-if="can('amenities:create')" label="Add Amenity" icon="pi pi-plus" @click="openCreateModal" />
        </template>
    </PageHeader>

    <!-- Filters -->
    <div class="mb-4 rounded-lg border border-gray-200 bg-white p-4">
        <div class="flex items-center gap-3">
            <Select
                v-model="groupFilter"
                :options="groupOptions"
                optionLabel="label"
                optionValue="value"
                placeholder="All Groups"
                showClear
                class="w-48"
            />
            <Button label="Clear" icon="pi pi-filter-slash" severity="secondary" text @click="clearFilters" />
        </div>
    </div>

    <!-- DataTable -->
    <div class="rounded-lg border border-gray-200 bg-white">
        <DataTable
            :value="amenities"
            stripedRows
            responsiveLayout="scroll"
            data-testid="amenities-table"
        >
            <Column field="name" header="Name" style="min-width: 200px">
                <template #body="{ data }">
                    <div class="font-medium">{{ data.name?.en ?? '–' }}</div>
                    <div v-if="data.name?.fr" class="text-xs text-gray-500">FR: {{ data.name.fr }}</div>
                </template>
            </Column>

            <Column field="group" header="Group" style="min-width: 120px">
                <template #body="{ data }">
                    <Tag :value="data.group" :severity="groupSeverity(data.group)" />
                </template>
            </Column>

            <Column field="icon" header="Icon" style="min-width: 80px">
                <template #body="{ data }">
                    <i v-if="data.icon" :class="data.icon" class="text-lg" />
                    <span v-else class="text-gray-400">–</span>
                </template>
            </Column>

            <Column field="sort_order" header="Order" style="min-width: 80px" />

            <Column field="is_active" header="Active" style="min-width: 80px">
                <template #body="{ data }">
                    <Tag :value="data.is_active ? 'Yes' : 'No'" :severity="data.is_active ? 'success' : 'danger'" />
                </template>
            </Column>

            <Column header="Actions" style="min-width: 100px" :exportable="false">
                <template #body="{ data }">
                    <div class="flex items-center gap-1">
                        <Button
                            v-if="can('amenities:create')"
                            icon="pi pi-pencil"
                            severity="warn"
                            text
                            rounded
                            size="small"
                            @click="openEditModal(data)"
                        />
                        <Button
                            v-if="can('amenities:delete')"
                            icon="pi pi-trash"
                            severity="danger"
                            text
                            rounded
                            size="small"
                            @click="deleteAmenity(data.id)"
                        />
                    </div>
                </template>
            </Column>

            <template #empty>
                <div class="py-8 text-center text-gray-500">No amenities found.</div>
            </template>
        </DataTable>
    </div>

    <!-- Create / Edit Modal -->
    <Dialog
        v-model:visible="showFormModal"
        :header="editingAmenity ? 'Edit Amenity' : 'Create Amenity'"
        modal
        :style="{ width: '550px' }"
        data-testid="amenity-form-modal"
    >
        <form @submit.prevent="submitForm" class="flex flex-col gap-4">
            <div>
                <label class="mb-1 block text-sm font-medium">Group *</label>
                <Select
                    v-model="form.group"
                    :options="groupOptions"
                    optionLabel="label"
                    optionValue="value"
                    placeholder="Select group..."
                    class="w-full"
                    :invalid="!!form.errors.group"
                />
                <small v-if="form.errors.group" class="text-red-500">{{ form.errors.group }}</small>
            </div>

            <MultilingualInput
                v-model="form.name"
                label="Name"
                :required="true"
                :errors="form.errors"
            />

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="mb-1 block text-sm font-medium">Icon</label>
                    <InputText v-model="form.icon" class="w-full" placeholder="pi pi-check" />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Sort Order</label>
                    <InputNumber v-model="form.sort_order" class="w-full" :min="0" />
                </div>
            </div>

            <div class="flex items-center gap-2">
                <InputSwitch v-model="form.is_active" />
                <span class="text-sm">Active</span>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <Button label="Cancel" severity="secondary" text @click="showFormModal = false" />
                <Button type="submit" :label="editingAmenity ? 'Update' : 'Create'" :loading="form.processing" />
            </div>
        </form>
    </Dialog>
</template>
