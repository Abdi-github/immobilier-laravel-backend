<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import Button from 'primevue/button';

const actions = [
    { label: 'Add Property', icon: 'pi pi-plus', route: '/admin/properties/create', severity: 'primary' as const },
    { label: 'Pending Review', icon: 'pi pi-clock', route: '/admin/properties/pending', severity: 'warn' as const },
    { label: 'View Leads', icon: 'pi pi-inbox', route: '/admin/leads', severity: 'info' as const },
    { label: 'Manage Users', icon: 'pi pi-users', route: '/admin/users', severity: 'secondary' as const },
];
</script>

<template>
    <div class="rounded-lg border border-gray-200 bg-white p-6">
        <h3 class="mb-4 text-sm font-semibold text-gray-900">Quick Actions</h3>
        <div class="grid grid-cols-2 gap-3">
            <Link v-for="action in actions" :key="action.route" :href="action.route">
                <Button
                    :label="action.label"
                    :icon="action.icon"
                    :severity="action.severity"
                    outlined
                    class="w-full justify-start"
                />
            </Link>
        </div>
    </div>
</template>
