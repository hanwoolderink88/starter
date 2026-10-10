import { router } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
} from '@/components/ui/dialog';
import { destroy } from '@/routes/users';
import type { UserManagementData } from '@/types/generated';

export default function DeleteUserDialog({
    user,
    restoreDays,
    onClose,
}: {
    user: UserManagementData | null;
    restoreDays: number;
    onClose: () => void;
}) {
    const { t } = useLaravelReactI18n();

    function handleDelete() {
        if (!user) return;

        router.delete(destroy(user.id).url, {
            onSuccess: () => onClose(),
            // The last administrator cannot be deleted.
            onError: (errors) => {
                if (errors.user) {
                    toast.error(errors.user);
                    onClose();
                }
            },
        });
    }

    return (
        <Dialog
            open={user !== null}
            onOpenChange={(open) => !open && onClose()}
        >
            <DialogContent>
                <DialogTitle>{t('users.delete_dialog.title')}</DialogTitle>
                <DialogDescription>
                    {t('users.delete_dialog.description', {
                        name: user?.name ?? '',
                        days: String(restoreDays),
                    })}
                </DialogDescription>
                <DialogFooter className="gap-2">
                    <DialogClose asChild>
                        <Button variant="secondary">
                            {t('users.delete_dialog.cancel')}
                        </Button>
                    </DialogClose>
                    <Button variant="destructive" onClick={handleDelete}>
                        {t('users.delete_dialog.confirm')}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
