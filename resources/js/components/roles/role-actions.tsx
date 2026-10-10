import { router } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { useState } from 'react';
import DeactivateRoleDialog from '@/components/roles/deactivate-role-dialog';
import DeleteRoleDialog from '@/components/roles/delete-role-dialog';
import { Button } from '@/components/ui/button';
import { toggleActive } from '@/routes/roles';
import type { RoleData, RoleHolderData } from '@/types/generated';

/**
 * Activate or deactivate, and delete, next to Save on the edit page. The
 * protected role has neither (its policy refuses both).
 */
export default function RoleActions({
    role,
    holders,
}: {
    role: RoleData;
    holders: RoleHolderData[];
}) {
    const { t } = useLaravelReactI18n();
    const [dialog, setDialog] = useState<'deactivate' | 'delete' | null>(null);

    if (role.isProtected) {
        return null;
    }

    function activate() {
        router.post(toggleActive(role.id).url, {}, { preserveScroll: true });
    }

    return (
        <>
            <Button
                type="button"
                variant="outline"
                onClick={() =>
                    role.isActive ? setDialog('deactivate') : activate()
                }
            >
                {role.isActive
                    ? t('roles.actions.deactivate')
                    : t('roles.actions.activate')}
            </Button>
            <Button
                type="button"
                variant="outline"
                className="text-destructive hover:text-destructive"
                onClick={() => setDialog('delete')}
            >
                {t('roles.actions.delete')}
            </Button>

            <DeactivateRoleDialog
                role={role}
                open={dialog === 'deactivate'}
                onOpenChange={(open) => setDialog(open ? 'deactivate' : null)}
            />
            <DeleteRoleDialog
                role={role}
                holders={holders}
                open={dialog === 'delete'}
                onOpenChange={(open) => setDialog(open ? 'delete' : null)}
            />
        </>
    );
}
