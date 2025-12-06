<script setup lang="ts">
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PageHeader from '@/Components/Shared/PageHeader.vue';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Select from 'primevue/select';
import MultiSelect from 'primevue/multiselect';
import Button from 'primevue/button';
import Message from 'primevue/message';
import type { Category, Amenity, Canton, City } from '@/Types/models';
import { computed } from 'vue';

defineOptions({ layout: AdminLayout });

interface Props {
    categories: Category[];
    amenities: Amenity[];
    cantons: Canton[];
    cities: City[];
}

const props = defineProps<Props>();

const form = useForm({
    category_id: null as number | null,
    transaction_type: '' as string,
    price: null as number | null,
    currency: 'CHF',
    additional_costs: null as number | null,
    rooms: null as number | null,
    surface: null as number | null,
    address: '',
    canton_id: null as number | null,
    city_id: null as number | null,
    postal_code: '',
    amenities: [] as number[],
    status: 'DRAFT',
});

const filteredCities = computed(() => {
    if (!form.canton_id) return props.cities;
    return props.cities.filter(c => c.canton_id === form.canton_id);
});

const transactionTypes = [
    { label: 'Rent', value: 'rent' },
    { label: 'Buy', value: 'buy' },
];

function onCantonChange() {
    form.city_id = null;
}

function submit() {
    form.post(route('admin.properties.store'));
}
</script>

<template>
    <PageHeader title="Create Property" description="Add a new property listing">
        <template #actions>
            <Link :href="route('admin.properties.index')">
                <Button label="Back to List" icon="pi pi-arrow-left" severity="secondary" text />
            </Link>
        </template>
    </PageHeader>

    <form @submit.prevent="submit" class="space-y-6">
        <!-- Basic Info -->
        <div class="rounded-lg border border-gray-200 bg-white p-6">
            <h3 class="mb-4 text-lg font-semibold">Basic Information</h3>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Category *</label>
                    <Select
                        v-model="form.category_id"
                        :options="categories"
                        optionLabel="name"
                        optionValue="id"
                        placeholder="Select category"
                        class="w-full"
                        :class="{ 'p-invalid': form.errors.category_id }"
                    />
                    <Message v-if="form.errors.category_id" severity="error" :closable="false" class="mt-1">{{ form.errors.category_id }}</Message>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Transaction Type *</label>
                    <Select
                        v-model="form.transaction_type"
                        :options="transactionTypes"
                        optionLabel="label"
                        optionValue="value"
                        placeholder="Select type"
                        class="w-full"
                        :class="{ 'p-invalid': form.errors.transaction_type }"
                    />
                    <Message v-if="form.errors.transaction_type" severity="error" :closable="false" class="mt-1">{{ form.errors.transaction_type }}</Message>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Price *</label>
                    <InputNumber
                        v-model="form.price"
                        mode="currency"
                        currency="CHF"
                        locale="de-CH"
                        class="w-full"
                        :class="{ 'p-invalid': form.errors.price }"
                    />
                    <Message v-if="form.errors.price" severity="error" :closable="false" class="mt-1">{{ form.errors.price }}</Message>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Additional Costs</label>
                    <InputNumber
                        v-model="form.additional_costs"
                        mode="currency"
                        currency="CHF"
                        locale="de-CH"
                        class="w-full"
                    />
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Rooms</label>
                    <InputNumber v-model="form.rooms" :minFractionDigits="0" :maxFractionDigits="1" class="w-full" />
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Surface (m²)</label>
                    <InputNumber v-model="form.surface" :minFractionDigits="0" :maxFractionDigits="2" suffix=" m²" class="w-full" />
                </div>
            </div>
        </div>

        <!-- Location -->
        <div class="rounded-lg border border-gray-200 bg-white p-6">
            <h3 class="mb-4 text-lg font-semibold">Location</h3>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="md:col-span-2">
                    <label class="mb-1 block text-sm font-medium text-gray-700">Address *</label>
                    <InputText
                        v-model="form.address"
                        class="w-full"
                        :class="{ 'p-invalid': form.errors.address }"
                    />
                    <Message v-if="form.errors.address" severity="error" :closable="false" class="mt-1">{{ form.errors.address }}</Message>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Canton *</label>
                    <Select
                        v-model="form.canton_id"
                        :options="cantons"
                        optionLabel="code"
                        optionValue="id"
                        placeholder="Select canton"
                        class="w-full"
                        :class="{ 'p-invalid': form.errors.canton_id }"
                        @change="onCantonChange"
                    />
                    <Message v-if="form.errors.canton_id" severity="error" :closable="false" class="mt-1">{{ form.errors.canton_id }}</Message>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">City *</label>
                    <Select
                        v-model="form.city_id"
                        :options="filteredCities"
                        optionLabel="name"
                        optionValue="id"
                        placeholder="Select city"
                        filter
                        class="w-full"
                        :class="{ 'p-invalid': form.errors.city_id }"
                    />
                    <Message v-if="form.errors.city_id" severity="error" :closable="false" class="mt-1">{{ form.errors.city_id }}</Message>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Postal Code</label>
                    <InputText v-model="form.postal_code" class="w-full" />
                </div>
            </div>
        </div>

        <!-- Amenities -->
        <div class="rounded-lg border border-gray-200 bg-white p-6">
            <h3 class="mb-4 text-lg font-semibold">Amenities</h3>
            <MultiSelect
                v-model="form.amenities"
                :options="amenities"
                optionLabel="name"
                optionValue="id"
                placeholder="Select amenities"
                filter
                display="chip"
                class="w-full"
            />
        </div>

        <!-- Submit -->
        <div class="flex items-center justify-end gap-3">
            <Link :href="route('admin.properties.index')">
                <Button label="Cancel" severity="secondary" type="button" />
            </Link>
            <Button
                label="Create as Draft"
                icon="pi pi-save"
                type="submit"
                :loading="form.processing"
                :disabled="form.processing"
            />
        </div>
    </form>
</template>
