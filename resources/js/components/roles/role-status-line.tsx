import { useLaravelReactI18n } from 'laravel-react-i18n';
import RoleStatus from '@/components/roles/role-status';
import type { RoleData } from '@/types/generated';

/**
 * The line under a role's name: its status and who has it.
 */
export default function RoleStatusLine({ role }: { role: RoleData }) {
    const { tChoice } = useLaravelReactI18n();

    return (
        <p className="flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-muted-foreground">
            <RoleStatus role={role} />
            <span>· {tChoice('roles.meta.users', role.userCount)}</span>
        </p>
    );
}
