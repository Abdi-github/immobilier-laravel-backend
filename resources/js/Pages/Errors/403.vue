<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Button from 'primevue/button';
</script>

<template>
    <Head title="403 - Forbidden" />
    <div class="flex min-h-screen items-center justify-center bg-gray-50">
        <div class="text-center">
            <p class="text-6xl font-bold text-blue-600">403</p>
            <h1 class="mt-4 text-2xl font-bold text-gray-900">Access Denied</h1>
            <p class="mt-2 text-gray-500">You don't have permission to access this page.</p>
            <div class="mt-6">
                <Link href="/admin/dashboard">
                    <Button label="Go to Dashboard" icon="pi pi-home" />
                </Link>
            </div>
        </div>
    </div>
</template>
