<script setup lang="ts">
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PageHeader from '@/Components/Shared/PageHeader.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import type { Permission } from '@/Types/models';

defineOptions({ layout: AdminLayout });

interface PermissionRow {
    id: number;
    name: string;
    guard_name: string;
    display_name: Record<string, string> | null;
    description: Record<string, string> | null;
    resource: string;
    action: string;
    is_active: boolean;
    created_at: string;
}

interface Props {
    permissions: PermissionRow[];
    grouped: Record<string, PermissionRow[]>;
}

const props = defineProps<Props>();

const resourceColors: Record<string, string> = {
    users: 'info',
    properties: 'success',
    agencies: 'warn',
    leads: 'danger',
    translations: 'secondary',
    categories: 'info',
    amenities: 'info',
    locations: 'info',
    admin: 'warn',
    roles: 'danger',
    permissions: 'danger',
    favorites: 'success',
    alerts: 'success',
    analytics: 'secondary',
};
</script>

<template>
    <PageHeader title="Permissions" description="View all system permissions" />

    <!-- Grouped View -->
    <div class="space-y-4">
        <div v-for="(perms, resource) in grouped" :key="resource" class="rounded-lg border border-gray-200 bg-white">
            <div class="border-b border-gray-100 px-6 py-3">
                <div class="flex items-center gap-2">
                    <Tag :value="String(resource)" :severity="resourceColors[String(resource)] ?? 'secondary'" />
                    <span class="text-sm text-gray-500">({{ perms.length }} permissions)</span>
                </div>
            </div>

            <DataTable :value="perms" :showHeader="true" responsiveLayout="scroll" :data-testid="`permissions-${resource}`">
                <Column field="action" header="Action" style="min-width: 120px">
                    <template #body="{ data }">
                        <span class="font-medium">{{ data.action }}</span>
                    </template>
                </Column>

                <Column field="name" header="Full Name" style="min-width: 200px">
                    <template #body="{ data }">
                        <code class="rounded bg-gray-100 px-2 py-0.5 text-sm">{{ data.name }}</code>
                    </template>
                </Column>

                <Column field="display_name" header="Display Name" style="min-width: 200px">
                    <template #body="{ data }">
                        {{ data.display_name?.en ?? '–' }}
                    </template>
                </Column>

                <Column field="is_active" header="Active" style="min-width: 80px">
                    <template #body="{ data }">
                        <Tag :value="data.is_active ? 'Yes' : 'No'" :severity="data.is_active ? 'success' : 'danger'" />
                    </template>
                </Column>
            </DataTable>
        </div>
    </div>

    <!-- Summary -->
    <div class="mt-6 text-center text-sm text-gray-500">
        Total: {{ permissions.length }} permissions across {{ Object.keys(grouped).length }} resources
    </div>
</template>
