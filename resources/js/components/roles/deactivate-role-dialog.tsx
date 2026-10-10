import { router } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
} from '@/components/ui/dialog';
import { toggleActive } from '@/routes/roles';
import type { RoleData } from '@/types/generated';

/**
 * Asks before deactivating a role, naming how many users lose its
 * permissions; their assignments are kept.
 */
export default function DeactivateRoleDialog({
    role,
    open,
    onOpenChange,
}: {
    role: RoleData;
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    const { t, tChoice } = useLaravelReactI18n();

    function deactivate() {
        router.post(
            toggleActive(role.id).url,
            {},
            { preserveScroll: true, onSuccess: () => onOpenChange(false) },
        );
    }

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogTitle>
                    {t('roles.deactivate_dialog.title', { name: role.name })}
                </DialogTitle>
                <DialogDescription>
                    {role.userCount > 0
                        ? tChoice(
                              'roles.deactivate_dialog.body',
                              role.userCount,
                          )
                        : t('roles.deactivate_dialog.body_unassigned')}
                </DialogDescription>
                <DialogFooter className="gap-2">
                    <DialogClose asChild>
                        <Button variant="outline">
                            {t('roles.deactivate_dialog.cancel')}
                        </Button>
                    </DialogClose>
                    <Button onClick={deactivate}>
                        {t('roles.deactivate_dialog.confirm')}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
