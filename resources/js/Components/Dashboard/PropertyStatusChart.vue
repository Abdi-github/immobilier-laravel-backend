<script setup lang="ts">
import { computed } from 'vue';
import { Doughnut } from 'vue-chartjs';
import { Chart as ChartJS, ArcElement, Tooltip, Legend } from 'chart.js';

ChartJS.register(ArcElement, Tooltip, Legend);

interface Props {
    labels: string[];
    data: number[];
    title?: string;
}

const props = withDefaults(defineProps<Props>(), {
    title: 'Property Status',
});

const statusColors: Record<string, string> = {
    Draft: '#94a3b8',
    'Pending approval': '#f59e0b',
    Approved: '#22c55e',
    Rejected: '#ef4444',
    Published: '#3b82f6',
    Archived: '#6b7280',
};

const backgroundColors = computed(() =>
    props.labels.map((label) => statusColors[label] ?? '#94a3b8'),
);

const chartData = computed(() => ({
    labels: props.labels,
    datasets: [
        {
            data: props.data,
            backgroundColor: backgroundColors.value,
            borderWidth: 0,
            hoverOffset: 4,
        },
    ],
}));

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'bottom' as const,
            labels: {
                padding: 16,
                usePointStyle: true,
                pointStyleWidth: 10,
                font: { size: 12 },
            },
        },
    },
    cutout: '65%',
};
</script>

<template>
    <div class="rounded-lg border border-gray-200 bg-white p-6">
        <h3 class="mb-4 text-sm font-semibold text-gray-900">{{ title }}</h3>
        <div class="h-64">
            <Doughnut :data="chartData" :options="chartOptions" />
        </div>
    </div>
</template>
