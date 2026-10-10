import { router } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { useState } from 'react';
import { toast } from 'sonner';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useInitials } from '@/hooks/use-initials';
import { destroy } from '@/routes/roles';
import type { RoleData, RoleHolderData } from '@/types/generated';

type Props = {
    role: RoleData;
    /** The first holders; `role.userCount` has the total. */
    holders: RoleHolderData[];
    open: boolean;
    onOpenChange: (open: boolean) => void;
};

/**
 * Deleting a role. A role that someone still has cannot go: the dialog names
 * who has it and only offers Close. A free role asks for its name to be
 * typed; that is a client-side gate, the server guards the rule itself.
 */
export default function DeleteRoleDialog(props: Props) {
    return (
        <Dialog open={props.open} onOpenChange={props.onOpenChange}>
            <DialogContent>
                {props.role.userCount > 0 ? (
                    <Blocked {...props} />
                ) : (
                    // Remount on open, so the typed name starts empty.
                    props.open && <ConfirmByName {...props} />
                )}
            </DialogContent>
        </Dialog>
    );
}

function Blocked({ role, holders }: Props) {
    const { t, tChoice } = useLaravelReactI18n();
    const getInitials = useInitials();
    const hidden = role.userCount - holders.length;

    return (
        <>
            <DialogTitle>
                {t('roles.blocked_dialog.title', { name: role.name })}
            </DialogTitle>
            <DialogDescription>
                {tChoice('roles.blocked_dialog.body', role.userCount)}
            </DialogDescription>
            <ul className="space-y-1 rounded-lg bg-muted p-2">
                {holders.map((holder) => (
                    <li
                        key={holder.id}
                        className="flex items-center gap-3 px-1 py-1"
                    >
                        <Avatar className="size-7">
                            <AvatarFallback className="text-xs font-semibold">
                                {getInitials(holder.name)}
                            </AvatarFallback>
                        </Avatar>
                        <span className="min-w-0 truncate">{holder.name}</span>
                    </li>
                ))}
                {hidden > 0 && (
                    <li className="px-1 py-1 text-sm text-muted-foreground">
                        {t('roles.blocked_dialog.more', { count: hidden })}
                    </li>
                )}
            </ul>
            <DialogFooter>
                <DialogClose asChild>
                    <Button variant="outline">
                        {t('roles.blocked_dialog.close')}
                    </Button>
                </DialogClose>
            </DialogFooter>
        </>
    );
}

function ConfirmByName({ role, onOpenChange }: Props) {
    const { t } = useLaravelReactI18n();
    const [typed, setTyped] = useState('');
    const [processing, setProcessing] = useState(false);

    function remove(e: React.FormEvent) {
        e.preventDefault();
        // The dialog is portalled, but React still bubbles the submit to the
        // role form around the button that opened it.
        e.stopPropagation();

        if (typed !== role.name) {
            return;
        }

        router.delete(destroy(role.id).url, {
            onStart: () => setProcessing(true),
            onFinish: () => setProcessing(false),
            onSuccess: () => onOpenChange(false),
            // Someone was given the role in the meantime.
            onError: (errors) => {
                if (errors.role) {
                    toast.error(errors.role);
                }
            },
        });
    }

    return (
        <form onSubmit={remove} className="grid gap-4">
            <DialogTitle>
                {t('roles.delete_dialog.title', { name: role.name })}
            </DialogTitle>
            <DialogDescription>
                {t('roles.delete_dialog.body')}
            </DialogDescription>
            <div className="grid gap-2">
                <Label htmlFor="confirm-role-name">
                    {t('roles.delete_dialog.type_to_confirm', {
                        name: role.name,
                    })}
                </Label>
                <Input
                    id="confirm-role-name"
                    value={typed}
                    onChange={(e) => setTyped(e.target.value)}
                    autoComplete="off"
                />
            </div>
            <DialogFooter className="gap-2">
                <DialogClose asChild>
                    <Button type="button" variant="outline">
                        {t('roles.delete_dialog.cancel')}
                    </Button>
                </DialogClose>
                <Button
                    variant="destructive"
                    disabled={typed !== role.name || processing}
                >
                    {t('roles.delete_dialog.confirm')}
                </Button>
            </DialogFooter>
        </form>
    );
}
