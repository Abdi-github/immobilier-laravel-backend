<script setup lang="ts">
import { computed, ref } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { useUiStore } from '@/Stores/uiStore';
import Button from 'primevue/button';
import Menu from 'primevue/menu';
import type { SharedProps } from '@/Types/models';

const uiStore = useUiStore();
const page = usePage<SharedProps>();

const user = computed(() => page.props.auth?.user);
const userMenu = ref();

const menuItems = ref([
    {
        label: 'Profile',
        icon: 'pi pi-user',
        command: () => router.visit('/admin/settings'),
    },
    { separator: true },
    {
        label: 'Logout',
        icon: 'pi pi-sign-out',
        command: () => router.post('/logout'),
    },
]);

function toggleUserMenu(event: Event) {
    userMenu.value.toggle(event);
}
</script>

<template>
    <header
        :class="[
            'fixed right-0 top-0 z-20 flex h-16 items-center justify-between border-b border-gray-200 bg-white px-6 transition-all duration-300',
            uiStore.sidebarCollapsed ? 'left-16' : 'left-64',
        ]"
    >
        <!-- Left side -->
        <div class="flex items-center gap-4">
            <button
                class="rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-600 lg:hidden"
                @click="uiStore.toggleSidebar()"
            >
                <i class="pi pi-bars" />
            </button>
        </div>

        <!-- Right side -->
        <div class="flex items-center gap-4">
            <!-- Locale switcher -->
            <select
                :value="uiStore.locale"
                class="rounded-md border border-gray-300 bg-white px-2 py-1 text-sm text-gray-600"
                @change="uiStore.setLocale(($event.target as HTMLSelectElement).value)"
            >
                <option value="en">EN</option>
                <option value="fr">FR</option>
                <option value="de">DE</option>
                <option value="it">IT</option>
            </select>

            <!-- User menu -->
            <div v-if="user" class="flex items-center">
                <Button
                    type="button"
                    class="flex items-center gap-2 rounded-lg p-2 text-sm text-gray-700 hover:bg-gray-100"
                    text
                    @click="toggleUserMenu"
                >
                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-100 text-sm font-medium text-blue-700">
                        {{ user.first_name.charAt(0) }}{{ user.last_name.charAt(0) }}
                    </div>
                    <span class="hidden md:inline">{{ user.first_name }} {{ user.last_name }}</span>
                    <i class="pi pi-chevron-down text-xs" />
                </Button>
                <Menu ref="userMenu" :model="menuItems" :popup="true" />
            </div>
        </div>
    </header>
</template>
