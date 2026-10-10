import { useLaravelReactI18n } from 'laravel-react-i18n';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';
import type { AssignableRoleData } from '@/types/generated';

/**
 * The roles of the user form as a checklist. An inactive role is shown but
 * cannot be chosen; an existing inactive assignment stays checked and locked,
 * with its own "Take away" button when `onRevoke` is given, because saving the
 * form never removes it.
 */
export default function RoleChecklist({
    roles,
    selectedIds,
    assignedIds = [],
    lockedIds = [],
    readOnly = false,
    onChange,
    onRevoke,
    revokingId = null,
    error,
}: {
    roles: AssignableRoleData[];
    /** The chosen active roles: what the form posts as `role_ids`. */
    selectedIds: number[];
    /** The roles the user has now, inactive ones included; [] when creating. */
    assignedIds?: number[];
    /** Chosen roles that cannot be unchecked, such as the last administrator's. */
    lockedIds?: number[];
    /** The viewer may not change roles: show them, change nothing. */
    readOnly?: boolean;
    onChange: (ids: number[]) => void;
    onRevoke?: (role: AssignableRoleData) => void;
    revokingId?: number | null;
    error?: string;
}) {
    const { t } = useLaravelReactI18n();

    function toggle(role: AssignableRoleData, checked: boolean) {
        onChange(
            checked
                ? [...selectedIds, role.id]
                : selectedIds.filter((id) => id !== role.id),
        );
    }

    return (
        <fieldset className="grid gap-2">
            <legend className="text-sm leading-none font-medium">
                {t('users.form.roles')}
            </legend>
            <p className="text-sm text-muted-foreground">
                {readOnly
                    ? t('users.form.roles_readonly')
                    : t('users.form.roles_hint')}
            </p>
            <ul className="divide-y rounded-lg border">
                {roles.map((role) => {
                    const inactiveAssignment =
                        !role.isActive && assignedIds.includes(role.id);
                    const disabled =
                        readOnly ||
                        !role.isActive ||
                        lockedIds.includes(role.id);
                    const inputId = `role-${role.id}`;

                    return (
                        <li
                            key={role.id}
                            className="flex items-center gap-3 px-4"
                        >
                            <Label
                                htmlFor={inputId}
                                className={cn(
                                    'flex min-h-12 min-w-0 grow items-center justify-between gap-3 py-2 font-normal',
                                    disabled
                                        ? 'cursor-not-allowed'
                                        : 'cursor-pointer',
                                )}
                            >
                                <span className="flex min-w-0 flex-col gap-0.5">
                                    <span
                                        className={cn(
                                            'font-medium',
                                            !role.isActive &&
                                                'text-muted-foreground',
                                        )}
                                    >
                                        {role.name}
                                    </span>
                                    {!role.isActive && (
                                        <span className="text-sm text-muted-foreground">
                                            {inactiveAssignment
                                                ? t(
                                                      'users.form.role_inactive_assigned',
                                                  )
                                                : t('users.form.role_inactive')}
                                        </span>
                                    )}
                                </span>
                                <Checkbox
                                    id={inputId}
                                    aria-label={role.name}
                                    checked={
                                        inactiveAssignment ||
                                        selectedIds.includes(role.id)
                                    }
                                    disabled={disabled}
                                    onCheckedChange={(checked) =>
                                        toggle(role, checked === true)
                                    }
                                    className="size-5 shrink-0"
                                />
                            </Label>
                            {inactiveAssignment && onRevoke && !readOnly && (
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    className="shrink-0"
                                    aria-label={t('users.form.revoke_label', {
                                        role: role.name,
                                    })}
                                    disabled={revokingId === role.id}
                                    onClick={() => onRevoke(role)}
                                >
                                    {t('users.form.revoke')}
                                </Button>
                            )}
                        </li>
                    );
                })}
            </ul>
            <InputError message={error} />
        </fieldset>
    );
}
