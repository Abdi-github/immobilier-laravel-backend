<script setup lang="ts">
import ProgressSpinner from 'primevue/progressspinner';

interface Props {
    loading: boolean;
}

defineProps<Props>();
</script>

<template>
    <div v-if="loading" class="absolute inset-0 z-10 flex items-center justify-center bg-white/70">
        <ProgressSpinner style="width: 50px; height: 50px" strokeWidth="4" />
    </div>
</template>
