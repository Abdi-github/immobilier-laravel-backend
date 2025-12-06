<script setup lang="ts">
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { useUiStore } from '@/Stores/uiStore';

const uiStore = useUiStore();
const page = usePage();

const currentUrl = computed(() => page.url);

interface NavItem {
    label: string;
    icon: string;
    route: string;
    permission?: string;
}

interface NavGroup {
    title: string;
    items: NavItem[];
}

const navigation: NavGroup[] = [
    {
        title: 'Main',
        items: [
            { label: 'Dashboard', icon: 'pi pi-home', route: '/admin/dashboard' },
        ],
    },
    {
        title: 'Management',
        items: [
            { label: 'Properties', icon: 'pi pi-building', route: '/admin/properties', permission: 'properties.view' },
            { label: 'Agencies', icon: 'pi pi-briefcase', route: '/admin/agencies', permission: 'agencies.view' },
            { label: 'Leads', icon: 'pi pi-users', route: '/admin/leads', permission: 'leads.view' },
        ],
    },
    {
        title: 'Catalog',
        items: [
            { label: 'Categories', icon: 'pi pi-tags', route: '/admin/categories', permission: 'categories.view' },
            { label: 'Amenities', icon: 'pi pi-list', route: '/admin/amenities', permission: 'amenities.view' },
            { label: 'Locations', icon: 'pi pi-map-marker', route: '/admin/locations', permission: 'locations.view' },
        ],
    },
    {
        title: 'System',
        items: [
            { label: 'Users', icon: 'pi pi-user', route: '/admin/users', permission: 'users.view' },
            { label: 'Roles', icon: 'pi pi-shield', route: '/admin/roles', permission: 'roles.view' },
            { label: 'Translations', icon: 'pi pi-language', route: '/admin/translations', permission: 'translations.view' },
            { label: 'Settings', icon: 'pi pi-cog', route: '/admin/settings', permission: 'settings.view' },
        ],
    },
];

function isActive(route: string): boolean {
    return currentUrl.value.startsWith(route);
}
</script>

<template>
    <aside
        :class="[
            'fixed left-0 top-0 z-30 flex h-screen flex-col border-r border-gray-200 bg-white transition-all duration-300',
            uiStore.sidebarCollapsed ? 'w-16' : 'w-64',
        ]"
    >
        <!-- Logo -->
        <div class="flex h-16 items-center border-b border-gray-200 px-4">
            <Link href="/admin/dashboard" class="flex items-center gap-3">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-600 text-white font-bold text-sm">
                    IM
                </div>
                <span v-if="!uiStore.sidebarCollapsed" class="text-lg font-bold text-gray-900">
                    Immobilier
                </span>
            </Link>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 overflow-y-auto px-3 py-4">
            <div v-for="group in navigation" :key="group.title" class="mb-6">
                <p
                    v-if="!uiStore.sidebarCollapsed"
                    class="mb-2 px-3 text-xs font-semibold uppercase tracking-wider text-gray-400"
                >
                    {{ group.title }}
                </p>
                <ul class="space-y-1">
                    <li v-for="item in group.items" :key="item.route">
                        <Link
                            :href="item.route"
                            :class="[
                                'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors',
                                isActive(item.route)
                                    ? 'bg-blue-50 text-blue-700'
                                    : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900',
                            ]"
                            :title="uiStore.sidebarCollapsed ? item.label : undefined"
                        >
                            <i :class="[item.icon, 'text-base']" />
                            <span v-if="!uiStore.sidebarCollapsed">{{ item.label }}</span>
                        </Link>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Collapse toggle -->
        <div class="border-t border-gray-200 p-3">
            <button
                class="flex w-full items-center justify-center rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-600"
                @click="uiStore.toggleSidebar()"
            >
                <i :class="uiStore.sidebarCollapsed ? 'pi pi-angle-right' : 'pi pi-angle-left'" />
            </button>
        </div>
    </aside>
</template>
