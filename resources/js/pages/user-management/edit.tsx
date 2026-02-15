import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import EditUserForm from '@/components/user-management/edit-user-form';
import AppLayout from '@/layouts/app-layout';
import { index } from '@/routes/users';
import type { BreadcrumbItem } from '@/types';

export default function EditUser({
    user,
    roles,
}: App.Features.UserManagement.Data.UserFormPageData) {
    if (!user) return null;

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Users',
            href: index().url,
        },
        {
            title: `Edit ${user.name}`,
            href: '#',
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit ${user.name}`} />

            <div className="mx-auto w-full max-w-2xl space-y-6 p-4">
                <Heading
                    title={`Edit ${user.name}`}
                    description="Update user information and role"
                />

                <EditUserForm user={user} roles={roles} />
            </div>
        </AppLayout>
    );
}
