<script setup lang="ts">
import { computed } from 'vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import type { PaginatedData } from '@/Types/models';

interface Props {
    data: PaginatedData<unknown>;
    loading?: boolean;
    globalFilterFields?: string[];
    searchPlaceholder?: string;
}

const props = withDefaults(defineProps<Props>(), {
    loading: false,
    globalFilterFields: () => [],
    searchPlaceholder: 'Search...',
});

const emit = defineEmits<{
    page: [event: { page: number; rows: number }];
    sort: [event: unknown];
    search: [query: string];
}>();

const totalRecords = computed(() => props.data.meta?.total ?? props.data.data.length);
const rows = computed(() => props.data.meta?.per_page ?? 15);
</script>

<template>
    <div class="rounded-lg border border-gray-200 bg-white">
        <div v-if="$slots.toolbar || globalFilterFields.length" class="flex items-center justify-between border-b border-gray-200 p-4">
            <div v-if="globalFilterFields.length" class="relative">
                <span class="p-input-icon-left">
                    <i class="pi pi-search" />
                    <InputText
                        :placeholder="searchPlaceholder"
                        class="w-72"
                        @input="emit('search', ($event.target as HTMLInputElement).value)"
                    />
                </span>
            </div>
            <div class="flex items-center gap-2">
                <slot name="toolbar" />
            </div>
        </div>

        <DataTable
            :value="data.data"
            :loading="loading"
            :lazy="true"
            :paginator="true"
            :rows="rows"
            :totalRecords="totalRecords"
            :rowsPerPageOptions="[10, 15, 25, 50]"
            paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
            @page="emit('page', $event)"
            @sort="emit('sort', $event)"
            stripedRows
            responsiveLayout="scroll"
        >
            <slot />

            <template #empty>
                <slot name="empty">
                    <div class="py-8 text-center text-gray-500">No records found.</div>
                </slot>
            </template>
        </DataTable>
    </div>
</template>
