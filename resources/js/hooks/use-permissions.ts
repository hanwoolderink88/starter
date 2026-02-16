import { usePage } from '@inertiajs/react';
import { useCallback } from 'react';
import type { Permission } from '@/types/generated';

export function usePermissions() {
    const { permissions } = usePage().props;

    const can = useCallback(
        (permission: Permission) => permissions.includes(permission),
        [permissions],
    );

    return { can };
}
