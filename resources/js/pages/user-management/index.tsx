import { Head, Link } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useState } from 'react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import DeleteUserDialog from '@/components/user-management/delete-user-dialog';
import UsersTable from '@/components/user-management/users-table';
import AppLayout from '@/layouts/app-layout';
import { create, index } from '@/routes/users';
import type { BreadcrumbItem } from '@/types';

type UserManagementData = App.Features.UserManagement.Data.UserManagementData;

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Users',
        href: index().url,
    },
];

export default function UsersIndex({
    users,
    canCreate,
    canImpersonate,
}: App.Features.UserManagement.Data.UsersPageData) {
    const [deleteTarget, setDeleteTarget] = useState<UserManagementData | null>(
        null,
    );

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Users" />

            <div className="mx-auto w-full max-w-5xl space-y-6 p-4">
                <div className="flex items-center justify-between">
                    <Heading
                        title="Users"
                        description="Manage users and their roles"
                    />
                    {canCreate && (
                        <Button asChild>
                            <Link href={create()} prefetch>
                                <Plus className="mr-2 size-4" />
                                Add User
                            </Link>
                        </Button>
                    )}
                </div>

                <UsersTable
                    users={users}
                    canImpersonate={canImpersonate}
                    onDeleteRequest={setDeleteTarget}
                />
            </div>

            <DeleteUserDialog
                user={deleteTarget}
                onClose={() => setDeleteTarget(null)}
            />
        </AppLayout>
    );
}
