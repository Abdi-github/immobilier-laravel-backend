<script setup lang="ts">
import { ref, computed } from 'vue';
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
import type { Category } from '@/Types/models';
import { useConfirm } from '@/Composables/useConfirm';
import { usePermissions } from '@/Composables/usePermissions';

defineOptions({ layout: AdminLayout });

interface Props {
    categories: (Category & { properties_count: number })[];
    sections: string[];
    filters: Record<string, string>;
}

const props = defineProps<Props>();

const { can } = usePermissions();
const { confirmDelete } = useConfirm();

// Filter
const sectionFilter = ref<string | null>(props.filters.section ?? null);

function reload() {
    router.get(route('admin.categories.index'), {
        section: sectionFilter.value || undefined,
    }, { preserveState: true, preserveScroll: true });
}

function clearFilters() {
    sectionFilter.value = null;
    reload();
}

const sectionOptions = computed(() =>
    props.sections.map(s => ({ label: s.charAt(0).toUpperCase() + s.slice(1), value: s }))
);

// Modal
const showFormModal = ref(false);
const editingCategory = ref<Category | null>(null);

const form = useForm({
    section: '' as string,
    name: { en: '', fr: '', de: '', it: '' } as Record<string, string>,
    icon: '',
    sort_order: 0,
    is_active: true,
});

function openCreateModal() {
    editingCategory.value = null;
    form.reset();
    form.is_active = true;
    form.name = { en: '', fr: '', de: '', it: '' };
    form.clearErrors();
    showFormModal.value = true;
}

function openEditModal(category: Category) {
    editingCategory.value = category;
    form.section = category.section;
    form.name = { en: '', fr: '', de: '', it: '', ...category.name };
    form.icon = category.icon ?? '';
    form.sort_order = category.sort_order;
    form.is_active = category.is_active;
    form.clearErrors();
    showFormModal.value = true;
}

function submitForm() {
    if (editingCategory.value) {
        form.put(route('admin.categories.update', editingCategory.value.id), {
            onSuccess: () => { showFormModal.value = false; },
        });
    } else {
        form.post(route('admin.categories.store'), {
            onSuccess: () => { showFormModal.value = false; },
        });
    }
}

function deleteCategory(id: number) {
    confirmDelete(() => {
        router.delete(route('admin.categories.destroy', id));
    });
}

function sectionSeverity(section: string): 'success' | 'info' | 'warn' | 'danger' | 'secondary' {
    const map: Record<string, 'success' | 'info' | 'warn' | 'danger'> = {
        residential: 'success',
        commercial: 'info',
        land: 'warn',
        parking: 'secondary' as any,
        special: 'danger',
    };
    return map[section] ?? 'secondary';
}
</script>

<template>
    <PageHeader title="Categories" description="Manage property categories">
        <template #actions>
            <Button v-if="can('categories:create')" label="Add Category" icon="pi pi-plus" @click="openCreateModal" />
        </template>
    </PageHeader>

    <!-- Filters -->
    <div class="mb-4 rounded-lg border border-gray-200 bg-white p-4">
        <div class="flex items-center gap-3">
            <Select
                v-model="sectionFilter"
                :options="sectionOptions"
                optionLabel="label"
                optionValue="value"
                placeholder="All Sections"
                showClear
                class="w-48"
                @change="reload"
            />
            <Button label="Clear" icon="pi pi-filter-slash" severity="secondary" text @click="clearFilters" />
        </div>
    </div>

    <!-- DataTable -->
    <div class="rounded-lg border border-gray-200 bg-white">
        <DataTable
            :value="categories"
            stripedRows
            responsiveLayout="scroll"
            data-testid="categories-table"
        >
            <Column field="name" header="Name" style="min-width: 200px">
                <template #body="{ data }">
                    <div class="font-medium">{{ data.name?.en ?? '–' }}</div>
                    <div v-if="data.name?.fr" class="text-xs text-gray-500">FR: {{ data.name.fr }}</div>
                </template>
            </Column>

            <Column field="section" header="Section" style="min-width: 120px">
                <template #body="{ data }">
                    <Tag :value="data.section" :severity="sectionSeverity(data.section)" />
                </template>
            </Column>

            <Column field="icon" header="Icon" style="min-width: 80px">
                <template #body="{ data }">
                    <i v-if="data.icon" :class="data.icon" class="text-lg" />
                    <span v-else class="text-gray-400">–</span>
                </template>
            </Column>

            <Column field="sort_order" header="Order" style="min-width: 80px" />

            <Column field="properties_count" header="Properties" style="min-width: 100px">
                <template #body="{ data }">
                    {{ data.properties_count ?? 0 }}
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
                        <Button
                            v-if="can('categories:create')"
                            icon="pi pi-pencil"
                            severity="warn"
                            text
                            rounded
                            size="small"
                            @click="openEditModal(data)"
                        />
                        <Button
                            v-if="can('categories:delete')"
                            icon="pi pi-trash"
                            severity="danger"
                            text
                            rounded
                            size="small"
                            @click="deleteCategory(data.id)"
                        />
                    </div>
                </template>
            </Column>

            <template #empty>
                <div class="py-8 text-center text-gray-500">No categories found.</div>
            </template>
        </DataTable>
    </div>

    <!-- Create / Edit Modal -->
    <Dialog
        v-model:visible="showFormModal"
        :header="editingCategory ? 'Edit Category' : 'Create Category'"
        modal
        :style="{ width: '550px' }"
        data-testid="category-form-modal"
    >
        <form @submit.prevent="submitForm" class="flex flex-col gap-4">
            <div>
                <label class="mb-1 block text-sm font-medium">Section *</label>
                <Select
                    v-model="form.section"
                    :options="sectionOptions"
                    optionLabel="label"
                    optionValue="value"
                    placeholder="Select section..."
                    class="w-full"
                    :invalid="!!form.errors.section"
                />
                <small v-if="form.errors.section" class="text-red-500">{{ form.errors.section }}</small>
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
                    <InputText v-model="form.icon" class="w-full" placeholder="pi pi-home" />
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
                <Button type="submit" :label="editingCategory ? 'Update' : 'Create'" :loading="form.processing" />
            </div>
        </form>
    </Dialog>
</template>
