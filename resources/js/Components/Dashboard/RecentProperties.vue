<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import StatusBadge from '@/Components/Shared/StatusBadge.vue';
import { formatCurrency } from '@/Utils/formatters';

interface RecentProperty {
    id: number;
    title: string;
    status: string;
    price: number;
    transaction_type: string;
    category: string | null;
    canton: string | null;
    city: string | null;
    owner: string | null;
    created_at: string;
}

interface Props {
    properties: RecentProperty[];
}

defineProps<Props>();
</script>

<template>
    <div class="rounded-lg border border-gray-200 bg-white">
        <div class="border-b border-gray-200 px-6 py-4">
            <h3 class="text-sm font-semibold text-gray-900">Recent Properties</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50">
                        <th class="px-6 py-3 text-left font-medium text-gray-500">Title</th>
                        <th class="px-6 py-3 text-left font-medium text-gray-500">Status</th>
                        <th class="px-6 py-3 text-left font-medium text-gray-500">Price</th>
                        <th class="px-6 py-3 text-left font-medium text-gray-500">Location</th>
                        <th class="px-6 py-3 text-left font-medium text-gray-500">Owner</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="property in properties"
                        :key="property.id"
                        class="border-b border-gray-50 hover:bg-gray-50"
                    >
                        <td class="px-6 py-3">
                            <Link
                                :href="`/admin/properties/${property.id}`"
                                class="font-medium text-blue-600 hover:text-blue-800"
                            >
                                {{ property.title }}
                            </Link>
                            <p class="text-xs text-gray-400">{{ property.category }}</p>
                        </td>
                        <td class="px-6 py-3">
                            <StatusBadge :status="property.status.toLowerCase()" type="property" />
                        </td>
                        <td class="px-6 py-3 whitespace-nowrap">
                            {{ formatCurrency(property.price) }}
                            <span v-if="property.transaction_type === 'RENT'" class="text-gray-400">/mo</span>
                        </td>
                        <td class="px-6 py-3 whitespace-nowrap text-gray-600">
                            {{ property.city }}<span v-if="property.canton">, {{ property.canton }}</span>
                        </td>
                        <td class="px-6 py-3 text-gray-600">{{ property.owner ?? '—' }}</td>
                    </tr>
                    <tr v-if="!properties.length">
                        <td colspan="5" class="px-6 py-8 text-center text-gray-400">No properties yet</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
