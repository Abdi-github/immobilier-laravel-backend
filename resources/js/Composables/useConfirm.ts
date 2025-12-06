import { useConfirm as usePrimeConfirm } from 'primevue/useconfirm';
import { useI18n } from 'vue-i18n';

export function useConfirm() {
    const confirm = usePrimeConfirm();
    const { t } = useI18n();

    function confirmDelete(onAccept: () => void, message?: string) {
        confirm.require({
            header: t('confirm.deleteTitle'),
            message: message ?? t('confirm.deleteMessage'),
            icon: 'pi pi-exclamation-triangle',
            rejectLabel: t('common.cancel'),
            acceptLabel: t('common.delete'),
            acceptClass: 'p-button-danger',
            accept: onAccept,
        });
    }

    return { confirmDelete, confirm };
}
