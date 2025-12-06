<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Toast from 'primevue/toast';

interface Props {
    title?: string;
}

withDefaults(defineProps<Props>(), {
    title: '',
});
</script>

<template>
    <div class="flex min-h-screen items-center justify-center bg-gray-100">
        <Head :title="title" />
        <Toast position="top-right" />

        <div class="w-full max-w-md">
            <div class="mb-8 text-center">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-blue-600 text-white font-bold text-lg">
                    IM
                </div>
                <h1 class="mt-4 text-2xl font-bold text-gray-900">Immobilier Admin</h1>
            </div>
            <slot />
        </div>
    </div>
</template>
