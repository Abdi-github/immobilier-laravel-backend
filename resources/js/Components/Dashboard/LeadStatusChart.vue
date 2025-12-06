<script setup lang="ts">
import { computed } from 'vue';
import { Bar } from 'vue-chartjs';
import { Chart as ChartJS, CategoryScale, LinearScale, BarElement, Tooltip, Legend } from 'chart.js';

ChartJS.register(CategoryScale, LinearScale, BarElement, Tooltip, Legend);

interface Props {
    labels: string[];
    data: number[];
    title?: string;
}

const props = withDefaults(defineProps<Props>(), {
    title: 'Lead Status',
});

const statusColors: Record<string, string> = {
    New: '#3b82f6',
    Contacted: '#8b5cf6',
    Qualified: '#22c55e',
    'Viewing scheduled': '#06b6d4',
    Negotiating: '#f59e0b',
    Won: '#10b981',
    Lost: '#ef4444',
    Archived: '#6b7280',
};

const backgroundColors = computed(() =>
    props.labels.map((label) => statusColors[label] ?? '#94a3b8'),
);

const chartData = computed(() => ({
    labels: props.labels,
    datasets: [
        {
            label: 'Leads',
            data: props.data,
            backgroundColor: backgroundColors.value,
            borderRadius: 6,
            maxBarThickness: 40,
        },
    ],
}));

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
    },
    scales: {
        y: {
            beginAtZero: true,
            ticks: { stepSize: 1, font: { size: 11 } },
            grid: { color: '#f1f5f9' },
        },
        x: {
            ticks: { font: { size: 11 } },
            grid: { display: false },
        },
    },
};
</script>

<template>
    <div class="rounded-lg border border-gray-200 bg-white p-6">
        <h3 class="mb-4 text-sm font-semibold text-gray-900">{{ title }}</h3>
        <div class="h-64">
            <Bar :data="chartData" :options="chartOptions" />
        </div>
    </div>
</template>
