import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import CreateUserForm from '@/components/user-management/create-user-form';
import AppLayout from '@/layouts/app-layout';
import { index } from '@/routes/users';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Users',
        href: index().url,
    },
    {
        title: 'Create User',
        href: '#',
    },
];

export default function CreateUser({
    roles,
}: App.Features.UserManagement.Data.UserFormPageData) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Create User" />

            <div className="mx-auto w-full max-w-2xl space-y-6 p-4">
                <Heading
                    title="Create User"
                    description="Add a new user to the system"
                />

                <CreateUserForm roles={roles} />
            </div>
        </AppLayout>
    );
}
