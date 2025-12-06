import { watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useToast as usePrimeToast } from 'primevue/usetoast';
import type { SharedProps } from '@/Types';

export function useToast() {
    const toast = usePrimeToast();
    const page = usePage<SharedProps>();

    function showSuccess(message: string) {
        toast.add({ severity: 'success', summary: 'Success', detail: message, life: 4000 });
    }

    function showError(message: string) {
        toast.add({ severity: 'error', summary: 'Error', detail: message, life: 6000 });
    }

    function showWarn(message: string) {
        toast.add({ severity: 'warn', summary: 'Warning', detail: message, life: 5000 });
    }

    // Auto-watch flash messages from Inertia shared props
    watch(
        () => page.props.flash,
        (flash) => {
            if (flash?.success) showSuccess(flash.success);
            if (flash?.error) showError(flash.error);
            if (flash?.warning) showWarn(flash.warning);
        },
        { immediate: true },
    );

    return { showSuccess, showError, showWarn };
}
