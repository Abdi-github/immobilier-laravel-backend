import { defineStore } from 'pinia';
import { ref } from 'vue';

export const useUiStore = defineStore('ui', () => {
    const sidebarCollapsed = ref(false);
    const locale = ref('en');

    function toggleSidebar() {
        sidebarCollapsed.value = !sidebarCollapsed.value;
    }

    function setLocale(newLocale: string) {
        locale.value = newLocale;
    }

    return {
        sidebarCollapsed,
        locale,
        toggleSidebar,
        setLocale,
    };
});
