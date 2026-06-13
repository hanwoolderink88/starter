import { Head, Link, router } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { Plus } from 'lucide-react';
import { useState } from 'react';
import Heading from '@/components/heading';
import Pagination from '@/components/pagination';
import { Button } from '@/components/ui/button';
import DeleteUserDialog from '@/components/user-management/delete-user-dialog';
import UsersFilters, {
    type UserFilterChange,
} from '@/components/user-management/users-filters';
import UsersTable from '@/components/user-management/users-table';
import { useRealtimeResource } from '@/hooks/use-realtime-resource';
import AppLayout from '@/layouts/app-layout';
import { create, index } from '@/routes/users';
import type { BreadcrumbItem, PageProps } from '@/types';
import type { UserSortColumn } from '@/types/generated';
import {
    SortDirection,
    type UserManagementData,
    type UsersPageData,
} from '@/types/generated';

export default function UsersIndex({
    users,
    filters,
    sort,
    roleOptions,
    statusOptions,
    canCreate,
    canImpersonate,
}: PageProps<UsersPageData>) {
    const { t } = useLaravelReactI18n();
    const [deleteTarget, setDeleteTarget] = useState<UserManagementData | null>(
        null,
    );

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: t('users.breadcrumb.index'),
            href: index().url,
        },
    ];

    // Current list query, used as the base for every filter/sort navigation so
    // changing one dimension preserves the others.
    const query = {
        search: filters.search ?? undefined,
        role: filters.role ?? undefined,
        status: filters.status ?? undefined,
        sort: sort.column,
        direction: sort.direction,
    };

    function visit(params: Record<string, string | number | undefined>) {
        router.get(index().url, params, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: ['users', 'filters', 'sort'],
        });
    }

    function handleFilterChange(change: UserFilterChange) {
        // Changing a filter resets pagination by omitting the `page` param.
        visit({
            ...query,
            ...Object.fromEntries(
                Object.entries(change).map(([key, value]) => [
                    key,
                    value ?? undefined,
                ]),
            ),
        });
    }

    function handleSort(column: UserSortColumn) {
        const direction =
            sort.column === column && sort.direction === SortDirection.Asc
                ? SortDirection.Desc
                : SortDirection.Asc;

        visit({ ...query, sort: column, direction });
    }

    // Keep the list in sync when anyone else creates, updates, or deletes a
    // user, and drop the stale prefetched edit page for the affected user.
    useRealtimeResource({
        channel: 'users',
        event: '.UserChanged',
        only: ['users'],
        mode: 'auto',
        invalidateTags: (event) => [`user.${event.id}`],
        describe: (event) =>
            t(`users.realtime.${event.action}`, {
                name: event.label,
                actor: event.actorName,
            }),
    });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={t('users.title')} />

            <div className="mx-auto w-full max-w-5xl space-y-6 p-4">
                <div className="flex items-center justify-between">
                    <Heading
                        title={t('users.title')}
                        description={t('users.description')}
                    />
                    {canCreate && (
                        <Button asChild>
                            <Link href={create()} prefetch>
                                <Plus className="mr-2 size-4" />
                                {t('users.actions.create')}
                            </Link>
                        </Button>
                    )}
                </div>

                <UsersFilters
                    filters={filters}
                    roleOptions={roleOptions}
                    statusOptions={statusOptions}
                    onChange={handleFilterChange}
                />

                <UsersTable
                    users={users.data}
                    roleOptions={roleOptions}
                    sort={sort}
                    canImpersonate={canImpersonate}
                    onSort={handleSort}
                    onDeleteRequest={setDeleteTarget}
                />

                <Pagination meta={users.meta} />
            </div>

            <DeleteUserDialog
                user={deleteTarget}
                onClose={() => setDeleteTarget(null)}
            />
        </AppLayout>
    );
}
