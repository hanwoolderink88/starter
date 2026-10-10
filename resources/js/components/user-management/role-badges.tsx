import { useLaravelReactI18n } from 'laravel-react-i18n';
import { Badge } from '@/components/ui/badge';

/** A user's active roles as badges, or "No roles". */
export default function RoleBadges({ roles }: { roles: string[] }) {
    const { t } = useLaravelReactI18n();

    if (roles.length === 0) {
        return (
            <span className="text-sm text-muted-foreground">
                {t('users.form.no_roles')}
            </span>
        );
    }

    return (
        <span className="flex flex-wrap gap-1">
            {roles.map((role) => (
                <Badge key={role} variant="secondary">
                    {role}
                </Badge>
            ))}
        </span>
    );
}
