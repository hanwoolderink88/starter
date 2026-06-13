import { Head } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import Heading from '@/components/heading';
import CreateUserForm from '@/components/user-management/create-user-form';
import AppLayout from '@/layouts/app-layout';
import { create, index } from '@/routes/users';
import type { BreadcrumbItem, PageProps } from '@/types';
import type { UserFormPageData } from '@/types/generated';

export default function CreateUser({ roles }: PageProps<UserFormPageData>) {
    const { t } = useLaravelReactI18n();

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: t('users.breadcrumb.index'),
            href: index().url,
        },
        {
            title: t('users.breadcrumb.create'),
            href: create().url,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={t('users.form.create_title')} />

            <div className="mx-auto w-full max-w-2xl space-y-6 p-4">
                <Heading
                    title={t('users.form.create_title')}
                    description={t('users.form.create_description')}
                />

                <CreateUserForm roles={roles} />
            </div>
        </AppLayout>
    );
}
