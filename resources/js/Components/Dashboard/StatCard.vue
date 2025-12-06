<script setup lang="ts">
import { computed } from 'vue';
import Card from 'primevue/card';

interface Props {
    title: string;
    value: string | number;
    icon: string;
    trend?: number;
    trendLabel?: string;
    color?: 'blue' | 'green' | 'yellow' | 'red' | 'purple';
}

const props = withDefaults(defineProps<Props>(), {
    trend: 0,
    trendLabel: '',
    color: 'blue',
});

const colorClasses: Record<string, string> = {
    blue: 'bg-blue-50 text-blue-600',
    green: 'bg-green-50 text-green-600',
    yellow: 'bg-yellow-50 text-yellow-600',
    red: 'bg-red-50 text-red-600',
    purple: 'bg-purple-50 text-purple-600',
};

const iconBg = computed(() => colorClasses[props.color] ?? colorClasses.blue);

const trendColor = computed(() => {
    if (props.trend > 0) return 'text-green-600';
    if (props.trend < 0) return 'text-red-600';
    return 'text-gray-500';
});

const trendIcon = computed(() => {
    if (props.trend > 0) return 'pi pi-arrow-up';
    if (props.trend < 0) return 'pi pi-arrow-down';
    return 'pi pi-minus';
});
</script>

<template>
    <Card class="shadow-sm">
        <template #content>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">{{ title }}</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900">{{ value }}</p>
                    <div v-if="trend !== 0 || trendLabel" class="mt-1 flex items-center gap-1 text-xs">
                        <i :class="[trendIcon, trendColor]" />
                        <span :class="trendColor">{{ Math.abs(trend) }}%</span>
                        <span v-if="trendLabel" class="text-gray-400">{{ trendLabel }}</span>
                    </div>
                </div>
                <div :class="['flex h-12 w-12 items-center justify-center rounded-lg', iconBg]">
                    <i :class="[icon, 'text-xl']" />
                </div>
            </div>
        </template>
    </Card>
</template>
