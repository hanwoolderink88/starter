import { useLaravelReactI18n } from 'laravel-react-i18n';
import { Lock } from 'lucide-react';
import { Card } from '@/components/ui/card';
import { cn } from '@/lib/utils';
import type { Permission, PermissionGroupData } from '@/types/generated';

type Props = {
    groups: PermissionGroupData[];
    value: Permission[];
    onChange: (value: Permission[]) => void;
    /** Read-only and always on: the protected administrator role. */
    locked?: boolean;
};

/**
 * The permission catalogue as switches per group. A list, not a table with a
 * column per role, so it fits on a phone.
 */
export default function PermissionMatrix({
    groups,
    value,
    onChange,
    locked = false,
}: Props) {
    const { t } = useLaravelReactI18n();

    function toggle(permission: Permission, enabled: boolean) {
        onChange(
            enabled
                ? [...value, permission]
                : value.filter((granted) => granted !== permission),
        );
    }

    return (
        <div className="grid items-start gap-4 md:grid-cols-2">
            {groups.map((group) => (
                <Card key={group.group} className="gap-0 px-5 py-4">
                    <h3 className="pb-1 text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                        {t(`roles.groups.${group.group}`)}
                    </h3>
                    <ul className="divide-y">
                        {group.permissions.map((permission) => {
                            const label = t(`roles.permissions.${permission}`);

                            return (
                                <li
                                    key={permission}
                                    className="flex min-h-14 items-center justify-between gap-4 py-2"
                                >
                                    <div className="min-w-0">
                                        <p className="font-medium">{label}</p>
                                        <p className="truncate font-mono text-xs text-muted-foreground">
                                            {permission}
                                        </p>
                                    </div>
                                    {locked ? (
                                        <Lock
                                            className="size-4 shrink-0 text-muted-foreground"
                                            aria-label={t(
                                                'roles.status.protected',
                                            )}
                                        />
                                    ) : (
                                        <PermissionSwitch
                                            label={label}
                                            checked={value.includes(permission)}
                                            onChange={(enabled) =>
                                                toggle(permission, enabled)
                                            }
                                        />
                                    )}
                                </li>
                            );
                        })}
                    </ul>
                </Card>
            ))}
        </div>
    );
}

/**
 * An on/off switch with a 40 px tap target. Built on a button, because the
 * project has no switch primitive and adding one needs a new dependency.
 */
function PermissionSwitch({
    label,
    checked,
    onChange,
}: {
    label: string;
    checked: boolean;
    onChange: (checked: boolean) => void;
}) {
    return (
        <button
            type="button"
            role="switch"
            aria-checked={checked}
            aria-label={label}
            onClick={() => onChange(!checked)}
            className="-mr-1 flex size-10 shrink-0 items-center justify-center rounded-md focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
        >
            <span
                className={cn(
                    'flex h-5 w-9 items-center rounded-full border-2 border-transparent transition-colors',
                    checked ? 'bg-primary' : 'bg-input',
                )}
            >
                <span
                    className={cn(
                        'size-4 rounded-full bg-background shadow-sm transition-transform',
                        checked ? 'translate-x-4' : 'translate-x-0',
                    )}
                />
            </span>
        </button>
    );
}
