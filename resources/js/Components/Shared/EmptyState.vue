<script setup lang="ts">
interface Props {
    title: string;
    description?: string;
    icon?: string;
}

withDefaults(defineProps<Props>(), {
    description: '',
    icon: 'pi pi-inbox',
});
</script>

<template>
    <div class="flex flex-col items-center justify-center py-12 text-center">
        <i :class="[icon, 'text-4xl text-gray-300 mb-4']" />
        <h3 class="text-lg font-medium text-gray-900">{{ title }}</h3>
        <p v-if="description" class="mt-1 text-sm text-gray-500">{{ description }}</p>
        <div v-if="$slots.action" class="mt-4">
            <slot name="action" />
        </div>
    </div>
</template>
