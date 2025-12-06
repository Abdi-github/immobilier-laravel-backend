<script setup lang="ts">
import { computed } from 'vue';
import Tag from 'primevue/tag';

interface Props {
    status: string;
    type?: 'property' | 'account' | 'lead';
}

const props = withDefaults(defineProps<Props>(), {
    type: 'property',
});

const severityMap: Record<string, Record<string, string>> = {
    property: {
        DRAFT: 'secondary',
        PENDING_APPROVAL: 'warn',
        APPROVED: 'info',
        PUBLISHED: 'success',
        REJECTED: 'danger',
        ARCHIVED: 'secondary',
    },
    account: {
        active: 'success',
        inactive: 'danger',
        suspended: 'warn',
        pending_verification: 'warn',
    },
    lead: {
        new: 'info',
        contacted: 'warn',
        qualified: 'success',
        proposal: 'success',
        negotiation: 'warn',
        won: 'success',
        lost: 'danger',
        archived: 'secondary',
    },
};

const severity = computed(() => {
    return (severityMap[props.type]?.[props.status] ?? 'secondary') as 'success' | 'info' | 'warn' | 'danger' | 'secondary' | 'contrast' | undefined;
});

const label = computed(() => {
    return props.status.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
});
</script>

<template>
    <Tag :value="label" :severity="severity" />
</template>
