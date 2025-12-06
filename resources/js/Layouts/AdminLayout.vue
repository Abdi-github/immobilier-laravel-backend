<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Sidebar from './Components/Sidebar.vue';
import Topbar from './Components/Topbar.vue';
import { useUiStore } from '@/Stores/uiStore';
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';

interface Props {
    title?: string;
}

withDefaults(defineProps<Props>(), {
    title: '',
});

const uiStore = useUiStore();
</script>

<template>
    <div class="min-h-screen bg-gray-50">
        <Head :title="title" />
        <Toast position="top-right" />
        <ConfirmDialog />

        <Sidebar />
        <Topbar />

        <main
            :class="[
                'min-h-screen pt-16 transition-all duration-300',
                uiStore.sidebarCollapsed ? 'ml-16' : 'ml-64',
            ]"
        >
            <div class="p-6">
                <slot />
            </div>
        </main>
    </div>
</template>
