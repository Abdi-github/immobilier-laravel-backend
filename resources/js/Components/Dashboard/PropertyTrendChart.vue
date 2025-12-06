<script setup lang="ts">
import { computed } from 'vue';
import { Line } from 'vue-chartjs';
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Tooltip,
    Legend,
    Filler,
} from 'chart.js';

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Tooltip, Legend, Filler);

interface Props {
    labels: string[];
    data: number[];
    title?: string;
}

const props = withDefaults(defineProps<Props>(), {
    title: 'Properties Added (Last 6 Months)',
});

const chartData = computed(() => ({
    labels: props.labels,
    datasets: [
        {
            label: 'Properties',
            data: props.data,
            borderColor: '#3b82f6',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            fill: true,
            tension: 0.3,
            pointRadius: 4,
            pointBackgroundColor: '#3b82f6',
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
            <Line :data="chartData" :options="chartOptions" />
        </div>
    </div>
</template>
