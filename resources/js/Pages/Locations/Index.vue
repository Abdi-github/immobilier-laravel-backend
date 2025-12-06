<script setup lang="ts">
import { ref, watch } from 'vue';
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
import InputSwitch from 'primevue/inputswitch';
import Tag from 'primevue/tag';
import Tabs from 'primevue/tabs';
import TabList from 'primevue/tablist';
import Tab from 'primevue/tab';
import TabPanels from 'primevue/tabpanels';
import TabPanel from 'primevue/tabpanel';
import type { Canton, City } from '@/Types/models';
import { useConfirm } from '@/Composables/useConfirm';
import { usePermissions } from '@/Composables/usePermissions';
import { useDebounceFn } from '@vueuse/core';

defineOptions({ layout: AdminLayout });

interface CityWithCanton extends Omit<City, 'canton'> {
    canton?: { id: number; code: string };
}

interface Props {
    cantons: (Canton & { cities_count: number })[];
    cities: {
        data: CityWithCanton[];
        meta: { current_page: number; from: number | null; last_page: number; per_page: number; to: number | null; total: number };
    };
    filters: Record<string, string>;
}

const props = defineProps<Props>();

const { can } = usePermissions();
const { confirmDelete } = useConfirm();

const activeTab = ref('0');

// City filters
const cityCantonFilter = ref<number | null>(props.filters.canton_id ? Number(props.filters.canton_id) : null);
const citySearch = ref(props.filters.city_search ?? '');

function reloadCities(extra: Record<string, unknown> = {}) {
    router.get(route('admin.locations.index'), {
        canton_id: cityCantonFilter.value || undefined,
        city_search: citySearch.value || undefined,
        ...extra,
    }, { preserveState: true, preserveScroll: true });
}

const debouncedCitySearch = useDebounceFn(() => reloadCities(), 400);
watch(citySearch, () => debouncedCitySearch());
watch(cityCantonFilter, () => reloadCities());

function onCityPage(event: { page: number; rows: number }) {
    reloadCities({ city_page: event.page + 1, city_limit: event.rows });
}

function clearCityFilters() {
    cityCantonFilter.value = null;
    citySearch.value = '';
    reloadCities();
}

// Canton Modal
const showCantonModal = ref(false);
const editingCanton = ref<Canton | null>(null);

const cantonForm = useForm({
    code: '',
    name: { en: '', fr: '', de: '', it: '' } as Record<string, string>,
    is_active: true,
});

function openCreateCanton() {
    editingCanton.value = null;
    cantonForm.reset();
    cantonForm.is_active = true;
    cantonForm.name = { en: '', fr: '', de: '', it: '' };
    cantonForm.clearErrors();
    showCantonModal.value = true;
}

function openEditCanton(canton: Canton) {
    editingCanton.value = canton;
    cantonForm.code = canton.code;
    cantonForm.name = { en: '', fr: '', de: '', it: '', ...canton.name };
    cantonForm.is_active = canton.is_active;
    cantonForm.clearErrors();
    showCantonModal.value = true;
}

function submitCanton() {
    if (editingCanton.value) {
        cantonForm.put(route('admin.locations.cantons.update', editingCanton.value.id), {
            onSuccess: () => { showCantonModal.value = false; },
        });
    } else {
        cantonForm.post(route('admin.locations.cantons.store'), {
            onSuccess: () => { showCantonModal.value = false; },
        });
    }
}

function deleteCanton(id: number) {
    confirmDelete(() => {
        router.delete(route('admin.locations.cantons.destroy', id));
    });
}

// City Modal
const showCityModal = ref(false);
const editingCity = ref<CityWithCanton | null>(null);

const cityForm = useForm({
    canton_id: null as number | null,
    name: { en: '', fr: '', de: '', it: '' } as Record<string, string>,
    postal_code: '',
    is_active: true,
});

function openCreateCity() {
    editingCity.value = null;
    cityForm.reset();
    cityForm.is_active = true;
    cityForm.name = { en: '', fr: '', de: '', it: '' };
    cityForm.clearErrors();
    showCityModal.value = true;
}

function openEditCity(city: CityWithCanton) {
    editingCity.value = city;
    cityForm.canton_id = city.canton_id;
    cityForm.name = { en: '', fr: '', de: '', it: '', ...city.name };
    cityForm.postal_code = city.postal_code;
    cityForm.is_active = city.is_active;
    cityForm.clearErrors();
    showCityModal.value = true;
}

function submitCity() {
    if (editingCity.value) {
        cityForm.put(route('admin.locations.cities.update', editingCity.value.id), {
            onSuccess: () => { showCityModal.value = false; },
        });
    } else {
        cityForm.post(route('admin.locations.cities.store'), {
            onSuccess: () => { showCityModal.value = false; },
        });
    }
}

function deleteCity(id: number) {
    confirmDelete(() => {
        router.delete(route('admin.locations.cities.destroy', id));
    });
}
</script>

<template>
    <PageHeader title="Locations" description="Manage cantons and cities" />

    <Tabs :value="activeTab">
        <TabList>
            <Tab value="0">Cantons ({{ cantons.length }})</Tab>
            <Tab value="1">Cities ({{ cities.meta.total }})</Tab>
        </TabList>

        <TabPanels>
            <!-- Cantons Tab -->
            <TabPanel value="0">
                <div class="mb-4 flex justify-end">
                    <Button v-if="can('locations:create')" label="Add Canton" icon="pi pi-plus" @click="openCreateCanton" />
                </div>

                <div class="rounded-lg border border-gray-200 bg-white">
                    <DataTable :value="cantons" stripedRows responsiveLayout="scroll" data-testid="cantons-table">
                        <Column field="code" header="Code" style="min-width: 80px">
                            <template #body="{ data }">
                                <span class="font-mono font-bold">{{ data.code }}</span>
                            </template>
                        </Column>

                        <Column field="name" header="Name" style="min-width: 200px">
                            <template #body="{ data }">
                                <div class="font-medium">{{ data.name?.en ?? '–' }}</div>
                                <div v-if="data.name?.fr" class="text-xs text-gray-500">FR: {{ data.name.fr }}</div>
                            </template>
                        </Column>

                        <Column field="cities_count" header="Cities" style="min-width: 80px" />

                        <Column field="is_active" header="Active" style="min-width: 80px">
                            <template #body="{ data }">
                                <Tag :value="data.is_active ? 'Yes' : 'No'" :severity="data.is_active ? 'success' : 'danger'" />
                            </template>
                        </Column>

                        <Column header="Actions" style="min-width: 100px" :exportable="false">
                            <template #body="{ data }">
                                <div class="flex items-center gap-1">
                                    <Button
                                        v-if="can('locations:update')"
                                        icon="pi pi-pencil"
                                        severity="warn"
                                        text
                                        rounded
                                        size="small"
                                        @click="openEditCanton(data)"
                                    />
                                    <Button
                                        v-if="can('locations:delete')"
                                        icon="pi pi-trash"
                                        severity="danger"
                                        text
                                        rounded
                                        size="small"
                                        @click="deleteCanton(data.id)"
                                    />
                                </div>
                            </template>
                        </Column>

                        <template #empty>
                            <div class="py-8 text-center text-gray-500">No cantons found.</div>
                        </template>
                    </DataTable>
                </div>
            </TabPanel>

            <!-- Cities Tab -->
            <TabPanel value="1">
                <!-- City Filters -->
                <div class="mb-4 flex flex-wrap items-center gap-3">
                    <span class="p-input-icon-left">
                        <i class="pi pi-search" />
                        <InputText v-model="citySearch" placeholder="Search cities..." class="w-64" />
                    </span>

                    <Select
                        v-model="cityCantonFilter"
                        :options="cantons"
                        optionLabel="code"
                        optionValue="id"
                        placeholder="All Cantons"
                        showClear
                        class="w-40"
                    />

                    <Button label="Clear" icon="pi pi-filter-slash" severity="secondary" text @click="clearCityFilters" />

                    <div class="flex-1" />

                    <Button v-if="can('locations:create')" label="Add City" icon="pi pi-plus" @click="openCreateCity" />
                </div>

                <div class="rounded-lg border border-gray-200 bg-white">
                    <DataTable
                        :value="cities.data"
                        :lazy="true"
                        :paginator="true"
                        :rows="cities.meta.per_page"
                        :totalRecords="cities.meta.total"
                        :rowsPerPageOptions="[25, 50, 100]"
                        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
                        @page="onCityPage"
                        stripedRows
                        responsiveLayout="scroll"
                        data-testid="cities-table"
                    >
                        <Column field="postal_code" header="Postal Code" style="min-width: 100px">
                            <template #body="{ data }">
                                <span class="font-mono">{{ data.postal_code }}</span>
                            </template>
                        </Column>

                        <Column field="name" header="Name" style="min-width: 200px">
                            <template #body="{ data }">
                                <div class="font-medium">{{ data.name?.en ?? '–' }}</div>
                                <div v-if="data.name?.fr" class="text-xs text-gray-500">FR: {{ data.name.fr }}</div>
                            </template>
                        </Column>

                        <Column field="canton" header="Canton" style="min-width: 80px">
                            <template #body="{ data }">
                                <span class="font-mono">{{ data.canton?.code ?? '–' }}</span>
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
                                        v-if="can('locations:update')"
                                        icon="pi pi-pencil"
                                        severity="warn"
                                        text
                                        rounded
                                        size="small"
                                        @click="openEditCity(data)"
                                    />
                                    <Button
                                        v-if="can('locations:delete')"
                                        icon="pi pi-trash"
                                        severity="danger"
                                        text
                                        rounded
                                        size="small"
                                        @click="deleteCity(data.id)"
                                    />
                                </div>
                            </template>
                        </Column>

                        <template #empty>
                            <div class="py-8 text-center text-gray-500">No cities found.</div>
                        </template>
                    </DataTable>
                </div>
            </TabPanel>
        </TabPanels>
    </Tabs>

    <!-- Canton Modal -->
    <Dialog
        v-model:visible="showCantonModal"
        :header="editingCanton ? 'Edit Canton' : 'Create Canton'"
        modal
        :style="{ width: '500px' }"
        data-testid="canton-form-modal"
    >
        <form @submit.prevent="submitCanton" class="flex flex-col gap-4">
            <div>
                <label class="mb-1 block text-sm font-medium">Code *</label>
                <InputText
                    v-model="cantonForm.code"
                    class="w-full"
                    maxlength="2"
                    placeholder="VD"
                    :invalid="!!cantonForm.errors.code"
                />
                <small v-if="cantonForm.errors.code" class="text-red-500">{{ cantonForm.errors.code }}</small>
            </div>

            <MultilingualInput
                v-model="cantonForm.name"
                label="Name"
                :required="true"
                :errors="cantonForm.errors"
            />

            <div class="flex items-center gap-2">
                <InputSwitch v-model="cantonForm.is_active" />
                <span class="text-sm">Active</span>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <Button label="Cancel" severity="secondary" text @click="showCantonModal = false" />
                <Button type="submit" :label="editingCanton ? 'Update' : 'Create'" :loading="cantonForm.processing" />
            </div>
        </form>
    </Dialog>

    <!-- City Modal -->
    <Dialog
        v-model:visible="showCityModal"
        :header="editingCity ? 'Edit City' : 'Create City'"
        modal
        :style="{ width: '500px' }"
        data-testid="city-form-modal"
    >
        <form @submit.prevent="submitCity" class="flex flex-col gap-4">
            <div>
                <label class="mb-1 block text-sm font-medium">Canton *</label>
                <Select
                    v-model="cityForm.canton_id"
                    :options="cantons"
                    optionLabel="code"
                    optionValue="id"
                    placeholder="Select canton..."
                    class="w-full"
                    :invalid="!!cityForm.errors.canton_id"
                />
                <small v-if="cityForm.errors.canton_id" class="text-red-500">{{ cityForm.errors.canton_id }}</small>
            </div>

            <MultilingualInput
                v-model="cityForm.name"
                label="Name"
                :required="true"
                :errors="cityForm.errors"
            />

            <div>
                <label class="mb-1 block text-sm font-medium">Postal Code *</label>
                <InputText
                    v-model="cityForm.postal_code"
                    class="w-full"
                    placeholder="1000"
                    :invalid="!!cityForm.errors.postal_code"
                />
                <small v-if="cityForm.errors.postal_code" class="text-red-500">{{ cityForm.errors.postal_code }}</small>
            </div>

            <div class="flex items-center gap-2">
                <InputSwitch v-model="cityForm.is_active" />
                <span class="text-sm">Active</span>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <Button label="Cancel" severity="secondary" text @click="showCityModal = false" />
                <Button type="submit" :label="editingCity ? 'Update' : 'Create'" :loading="cityForm.processing" />
            </div>
        </form>
    </Dialog>
</template>
