import { Link, router, usePage } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import {
    ChevronDown,
    ChevronsUpDown,
    ChevronUp,
    Loader2,
    Mail,
    Pencil,
    RotateCcw,
    Trash2,
    UserCheck,
} from 'lucide-react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import RoleBadges from '@/components/user-management/role-badges';
import WithTooltip from '@/components/with-tooltip';
import { useImpersonation } from '@/hooks/use-impersonation';
import { usePermissions } from '@/hooks/use-permissions';
import { cn } from '@/lib/utils';
import { edit, resendInvitation, restore, show } from '@/routes/users';
import {
    Permission,
    SortDirection,
    UserSortColumn,
    type UserManagementData,
    type UserSortData,
} from '@/types/generated';

/** A deleted user who can still be restored: listed under the Deleted filter. */
function isDeleted(user: UserManagementData): boolean {
    return user.anonymizes_on_display !== null;
}

function SortableHead({
    column,
    label,
    sort,
    onSort,
    className,
}: {
    column: UserSortColumn;
    label: string;
    sort: UserSortData;
    onSort: (column: UserSortColumn) => void;
    className?: string;
}) {
    const active = sort.column === column;

    return (
        <TableHead className={className}>
            <button
                type="button"
                onClick={() => onSort(column)}
                className="-ml-1 inline-flex items-center gap-1 rounded px-1 py-0.5 hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                aria-label={label}
            >
                {label}
                {active ? (
                    sort.direction === SortDirection.Asc ? (
                        <ChevronUp className="size-3.5" />
                    ) : (
                        <ChevronDown className="size-3.5" />
                    )
                ) : (
                    <ChevronsUpDown className="size-3.5 opacity-50" />
                )}
            </button>
        </TableHead>
    );
}

export default function UsersTable({
    users,
    sort,
    impersonatableIds,
    onSort,
    onDeleteRequest,
}: {
    users: UserManagementData[];
    sort: UserSortData;
    /** The users on this page the viewer may impersonate now. */
    impersonatableIds: number[];
    onSort: (column: UserSortColumn) => void;
    onDeleteRequest: (user: UserManagementData) => void;
}) {
    const { t } = useLaravelReactI18n();
    const { auth } = usePage().props;
    const { start: impersonate } = useImpersonation();
    const { can } = usePermissions();
    const canRestore = can(Permission.DeleteUsers);
    const [resendingId, setResendingId] = useState<number | null>(null);

    if (!auth.user) return null;

    const currentUser = auth.user;

    function handleResendInvitation(user: UserManagementData) {
        router.post(
            resendInvitation(user.id).url,
            {},
            {
                preserveScroll: true,
                onStart: () => setResendingId(user.id),
                onFinish: () => setResendingId(null),
            },
        );
    }

    return (
        <Card className="p-0">
            <CardContent className="p-0">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <SortableHead
                                column={UserSortColumn.Name}
                                label={t('users.columns.name')}
                                sort={sort}
                                onSort={onSort}
                            />
                            <SortableHead
                                column={UserSortColumn.Email}
                                label={t('users.columns.email')}
                                sort={sort}
                                onSort={onSort}
                                className="hidden sm:table-cell"
                            />
                            <TableHead className="hidden md:table-cell">
                                {t('users.columns.role')}
                            </TableHead>
                            <TableHead className="hidden md:table-cell">
                                {t('users.columns.status')}
                            </TableHead>
                            <SortableHead
                                column={UserSortColumn.CreatedAt}
                                label={t('users.columns.created')}
                                sort={sort}
                                onSort={onSort}
                                className="hidden md:table-cell"
                            />
                            <TableHead className="text-right">
                                {t('users.columns.actions')}
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {users.length === 0 && (
                            <TableRow>
                                <TableCell
                                    colSpan={6}
                                    className="py-8 text-center text-muted-foreground"
                                >
                                    {t('users.empty')}
                                </TableCell>
                            </TableRow>
                        )}
                        {users.map((user: UserManagementData) => (
                            <TableRow
                                key={user.id}
                                onClick={() =>
                                    !isDeleted(user) &&
                                    router.visit(show(user.id).url)
                                }
                                className={cn(
                                    !isDeleted(user) && 'cursor-pointer',
                                )}
                            >
                                <TableCell className="font-medium">
                                    {isDeleted(user) ? (
                                        <span className="text-muted-foreground">
                                            {user.name}
                                        </span>
                                    ) : (
                                        // Real link so the row's default action
                                        // is keyboard- and screen-reader-accessible.
                                        <Link
                                            href={show(user.id)}
                                            className="hover:underline focus-visible:underline focus-visible:outline-none"
                                            onClick={(e) => e.stopPropagation()}
                                        >
                                            {user.name}
                                        </Link>
                                    )}
                                </TableCell>
                                <TableCell className="hidden sm:table-cell">
                                    {user.email}
                                </TableCell>
                                <TableCell className="hidden md:table-cell">
                                    <RoleBadges roles={user.roles} />
                                </TableCell>
                                <TableCell className="hidden md:table-cell">
                                    {isDeleted(user) ? (
                                        <div className="space-y-0.5">
                                            <Badge variant="destructive">
                                                {t('users.status.deleted')}
                                            </Badge>
                                            <p className="text-xs text-muted-foreground">
                                                {t(
                                                    'users.deleted.anonymizes_on',
                                                    {
                                                        date:
                                                            user.anonymizes_on_display ??
                                                            '',
                                                    },
                                                )}
                                            </p>
                                        </div>
                                    ) : (
                                        <Badge
                                            variant={
                                                user.has_password
                                                    ? 'success'
                                                    : 'warning'
                                            }
                                        >
                                            {user.has_password
                                                ? t('users.status.active')
                                                : t('users.status.invited')}
                                        </Badge>
                                    )}
                                </TableCell>
                                <TableCell className="hidden md:table-cell">
                                    {user.created_at_display}
                                </TableCell>
                                <TableCell
                                    className="text-right"
                                    onClick={(e) => e.stopPropagation()}
                                >
                                    {isDeleted(user) ? (
                                        canRestore && (
                                            <WithTooltip
                                                label={t(
                                                    'users.actions.restore',
                                                )}
                                            >
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="size-8"
                                                    aria-label={t(
                                                        'users.actions.restore',
                                                    )}
                                                    onClick={() =>
                                                        router.post(
                                                            restore(user.id)
                                                                .url,
                                                            {},
                                                            {
                                                                preserveScroll: true,
                                                            },
                                                        )
                                                    }
                                                >
                                                    <RotateCcw className="size-4" />
                                                </Button>
                                            </WithTooltip>
                                        )
                                    ) : (
                                        <div className="flex items-center justify-end gap-0.5">
                                            <WithTooltip
                                                label={t('users.actions.edit')}
                                            >
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="size-8"
                                                    aria-label={t(
                                                        'users.actions.edit',
                                                    )}
                                                    asChild
                                                >
                                                    <Link
                                                        href={edit(user.id)}
                                                        prefetch
                                                        cacheTags={[
                                                            `user.${user.id}`,
                                                        ]}
                                                    >
                                                        <Pencil className="size-4" />
                                                    </Link>
                                                </Button>
                                            </WithTooltip>

                                            {!user.has_password && (
                                                <WithTooltip
                                                    label={t(
                                                        'users.actions.resend_invitation',
                                                    )}
                                                >
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        className="size-8"
                                                        aria-label={t(
                                                            'users.actions.resend_invitation',
                                                        )}
                                                        onClick={() =>
                                                            handleResendInvitation(
                                                                user,
                                                            )
                                                        }
                                                        disabled={
                                                            resendingId ===
                                                            user.id
                                                        }
                                                    >
                                                        {resendingId ===
                                                        user.id ? (
                                                            <Loader2 className="size-4 animate-spin" />
                                                        ) : (
                                                            <Mail className="size-4" />
                                                        )}
                                                    </Button>
                                                </WithTooltip>
                                            )}

                                            {impersonatableIds.includes(
                                                user.id,
                                            ) && (
                                                <WithTooltip
                                                    label={t(
                                                        'users.actions.impersonate',
                                                    )}
                                                >
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        className="size-8"
                                                        aria-label={t(
                                                            'users.actions.impersonate',
                                                        )}
                                                        onClick={() =>
                                                            impersonate(user.id)
                                                        }
                                                    >
                                                        <UserCheck className="size-4" />
                                                    </Button>
                                                </WithTooltip>
                                            )}

                                            {user.id !== currentUser.id && (
                                                <WithTooltip
                                                    label={t(
                                                        'users.actions.delete',
                                                    )}
                                                >
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        className="size-8 text-destructive hover:text-destructive"
                                                        aria-label={t(
                                                            'users.actions.delete',
                                                        )}
                                                        onClick={() =>
                                                            onDeleteRequest(
                                                                user,
                                                            )
                                                        }
                                                    >
                                                        <Trash2 className="size-4" />
                                                    </Button>
                                                </WithTooltip>
                                            )}
                                        </div>
                                    )}
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </CardContent>
        </Card>
    );
}
