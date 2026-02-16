import { router } from '@inertiajs/react';
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
    onClose,
}: {
    user: UserManagementData | null;
    onClose: () => void;
}) {
    function handleDelete() {
        if (!user) return;

        router.delete(destroy(user.id).url, {
            onSuccess: () => onClose(),
        });
    }

    return (
        <Dialog
            open={user !== null}
            onOpenChange={(open) => !open && onClose()}
        >
            <DialogContent>
                <DialogTitle>Delete user</DialogTitle>
                <DialogDescription>
                    Are you sure you want to delete{' '}
                    <strong>{user?.name}</strong>? This action cannot be undone.
                </DialogDescription>
                <DialogFooter className="gap-2">
                    <DialogClose asChild>
                        <Button variant="secondary">Cancel</Button>
                    </DialogClose>
                    <Button variant="destructive" onClick={handleDelete}>
                        Delete
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
