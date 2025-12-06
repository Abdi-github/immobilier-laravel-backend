import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import type { SharedProps } from '@/Types';

export function usePermissions() {
    const page = usePage<SharedProps>();

    const permissions = computed(() => page.props.auth?.permissions ?? []);
    const roles = computed(() => page.props.auth?.roles ?? []);

    function can(permission: string): boolean {
        const perms = permissions.value;

        // Super admin wildcard
        if (perms.includes('*')) return true;

        // Direct match
        if (perms.includes(permission)) return true;

        // Resource wildcard (e.g. 'properties:*' grants 'properties:read')
        if (permission.includes(':')) {
            const [resource] = permission.split(':');
            if (perms.includes(`${resource}:*`)) return true;
            if (perms.includes(`${resource}:manage`)) return true;
        }

        return false;
    }

    function hasRole(role: string): boolean {
        return roles.value.includes(role);
    }

    function hasAnyPermission(permissionList: string[]): boolean {
        return permissionList.some((p) => can(p));
    }

    return { can, hasRole, hasAnyPermission, permissions, roles };
}
