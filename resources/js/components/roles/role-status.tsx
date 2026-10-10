import { useLaravelReactI18n } from 'laravel-react-i18n';
import { cn } from '@/lib/utils';
import type { RoleData } from '@/types/generated';

/** A dot and "Active" or "Inactive". */
export default function RoleStatus({ role }: { role: RoleData }) {
    const { t } = useLaravelReactI18n();

    return (
        <span
            className={cn(
                'inline-flex items-center gap-2',
                role.isActive ? 'text-foreground' : 'text-muted-foreground',
            )}
        >
            <span
                aria-hidden
                className={cn(
                    'size-2 rounded-full',
                    role.isActive
                        ? 'bg-success'
                        : 'border border-muted-foreground',
                )}
            />
            {role.isActive
                ? t('roles.status.active')
                : t('roles.status.inactive')}
        </span>
    );
}
