import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

export function usePermissions() {
    const page = usePage();

    const permissions = computed(
        () => new Set(page.props.auth?.permissions ?? []),
    );
    const roles = computed(() => page.props.auth?.roles ?? []);

    const can = (permission?: string): boolean =>
        !permission || permissions.value.has(permission);

    const canAny = (...list: string[]): boolean => list.some((p) => can(p));

    return { can, canAny, roles, permissions };
}
