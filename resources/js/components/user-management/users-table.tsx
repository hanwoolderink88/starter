import { Link, router, usePage } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import {
    ChevronDown,
    ChevronsUpDown,
    ChevronUp,
    Loader2,
    Mail,
    Pencil,
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
import WithTooltip from '@/components/with-tooltip';
import { edit, impersonate, resendInvitation, show } from '@/routes/users';
import {
    SortDirection,
    UserSortColumn,
    type UserManagementData,
    type UserSortData,
} from '@/types/generated';

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
    roleOptions,
    sort,
    canImpersonate,
    onSort,
    onDeleteRequest,
}: {
    users: UserManagementData[];
    roleOptions: Record<string, string>;
    sort: UserSortData;
    canImpersonate: boolean;
    onSort: (column: UserSortColumn) => void;
    onDeleteRequest: (user: UserManagementData) => void;
}) {
    const { t } = useLaravelReactI18n();
    const { auth } = usePage().props;
    const [resendingId, setResendingId] = useState<number | null>(null);

    if (!auth.user) return null;

    const currentUser = auth.user;

    function handleImpersonate(user: UserManagementData) {
        router.post(impersonate(user.id).url);
    }

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
                                onClick={() => router.visit(show(user.id).url)}
                                className="cursor-pointer"
                            >
                                <TableCell className="font-medium">
                                    {/* Real link so the row's default action is
                                        keyboard- and screen-reader-accessible. */}
                                    <Link
                                        href={show(user.id)}
                                        className="hover:underline focus-visible:underline focus-visible:outline-none"
                                        onClick={(e) => e.stopPropagation()}
                                    >
                                        {user.name}
                                    </Link>
                                </TableCell>
                                <TableCell className="hidden sm:table-cell">
                                    {user.email}
                                </TableCell>
                                <TableCell className="hidden md:table-cell">
                                    <Badge
                                        variant={
                                            user.role === 'super-admin'
                                                ? 'default'
                                                : 'secondary'
                                        }
                                    >
                                        {roleOptions[user.role] ?? user.role}
                                    </Badge>
                                </TableCell>
                                <TableCell className="hidden md:table-cell">
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
                                </TableCell>
                                <TableCell className="hidden md:table-cell">
                                    {user.created_at_display}
                                </TableCell>
                                <TableCell
                                    className="text-right"
                                    onClick={(e) => e.stopPropagation()}
                                >
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
                                                        resendingId === user.id
                                                    }
                                                >
                                                    {resendingId === user.id ? (
                                                        <Loader2 className="size-4 animate-spin" />
                                                    ) : (
                                                        <Mail className="size-4" />
                                                    )}
                                                </Button>
                                            </WithTooltip>
                                        )}

                                        {canImpersonate &&
                                            user.id !== currentUser.id && (
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
                                                            handleImpersonate(
                                                                user,
                                                            )
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
                                                        onDeleteRequest(user)
                                                    }
                                                >
                                                    <Trash2 className="size-4" />
                                                </Button>
                                            </WithTooltip>
                                        )}
                                    </div>
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </CardContent>
        </Card>
    );
}
