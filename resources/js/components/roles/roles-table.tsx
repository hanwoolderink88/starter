import { Link } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { ChevronRight, Lock } from 'lucide-react';
import RoleStatus from '@/components/roles/role-status';
import { Badge } from '@/components/ui/badge';
import { Card } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { cn } from '@/lib/utils';
import { edit } from '@/routes/roles';
import type { RoleData } from '@/types/generated';

/**
 * The roles as a table from `md`, and as a list of cards on a phone. Inactive
 * roles are dimmed; the administrator carries a Protected label.
 */
export default function RolesTable({
    roles,
    permissionTotal,
}: {
    roles: RoleData[];
    permissionTotal: number;
}) {
    const { t, tChoice } = useLaravelReactI18n();

    if (roles.length === 0) {
        return (
            <Card className="p-8 text-center text-sm text-muted-foreground">
                {t('roles.list.empty')}
            </Card>
        );
    }

    function roleLink(
        role: RoleData,
        className: string,
        children: React.ReactNode,
    ) {
        return (
            <Link
                key={role.id}
                href={edit(role.id)}
                prefetch
                cacheTags={[`role.${role.id}`]}
                className={className}
                aria-label={t('roles.actions.open', { name: role.name })}
            >
                {children}
            </Link>
        );
    }

    return (
        <>
            <Card className="gap-0 divide-y p-0 md:hidden">
                {roles.map((role) =>
                    roleLink(
                        role,
                        'flex min-h-16 items-center gap-3 px-4 py-3',
                        <>
                            <div className="min-w-0 flex-1">
                                <p
                                    className={cn(
                                        'truncate font-semibold',
                                        !role.isActive &&
                                            'text-muted-foreground',
                                    )}
                                >
                                    {role.name}
                                </p>
                                <p className="truncate text-sm text-muted-foreground">
                                    {role.permissionCount} / {permissionTotal}
                                    {' · '}
                                    {tChoice(
                                        'roles.list.users',
                                        role.userCount,
                                    )}
                                </p>
                            </div>
                            {role.isProtected && (
                                <Lock
                                    className="size-4 shrink-0 text-muted-foreground"
                                    aria-label={t('roles.status.protected')}
                                />
                            )}
                            {!role.isActive && (
                                <Badge variant="outline" className="shrink-0">
                                    {t('roles.status.inactive')}
                                </Badge>
                            )}
                        </>,
                    ),
                )}
            </Card>

            <Card className="hidden p-0 md:block">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead className="pl-5">
                                {t('roles.columns.role')}
                            </TableHead>
                            <TableHead>{t('roles.columns.status')}</TableHead>
                            <TableHead className="text-right">
                                {t('roles.columns.permissions')}
                            </TableHead>
                            <TableHead className="text-right">
                                {t('roles.columns.users')}
                            </TableHead>
                            <TableHead className="w-12">
                                <span className="sr-only">
                                    {t('roles.columns.role')}
                                </span>
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {roles.map((role) => {
                            const dimmed =
                                !role.isActive && 'text-muted-foreground';

                            return (
                                <TableRow key={role.id} className="relative">
                                    <TableCell className="py-3 pl-5">
                                        <div className="flex items-center gap-2">
                                            {roleLink(
                                                role,
                                                cn(
                                                    'font-semibold after:absolute after:inset-0 focus-visible:underline focus-visible:outline-none',
                                                    dimmed,
                                                ),
                                                role.name,
                                            )}
                                            {role.isProtected && (
                                                <Badge
                                                    variant="secondary"
                                                    className="shrink-0 gap-1"
                                                >
                                                    <Lock className="size-3" />
                                                    {t(
                                                        'roles.status.protected',
                                                    )}
                                                </Badge>
                                            )}
                                        </div>
                                        {role.description && (
                                            <p className="text-sm whitespace-normal text-muted-foreground">
                                                {role.description}
                                            </p>
                                        )}
                                    </TableCell>
                                    <TableCell>
                                        <RoleStatus role={role} />
                                    </TableCell>
                                    <TableCell
                                        className={cn(
                                            'text-right tabular-nums',
                                            dimmed,
                                        )}
                                    >
                                        {role.permissionCount} /{' '}
                                        {permissionTotal}
                                    </TableCell>
                                    <TableCell
                                        className={cn(
                                            'text-right tabular-nums',
                                            dimmed,
                                        )}
                                    >
                                        {role.userCount}
                                    </TableCell>
                                    <TableCell className="pr-5 text-right">
                                        <ChevronRight
                                            aria-hidden
                                            className="ml-auto size-4 text-muted-foreground"
                                        />
                                    </TableCell>
                                </TableRow>
                            );
                        })}
                    </TableBody>
                </Table>
            </Card>
        </>
    );
}
