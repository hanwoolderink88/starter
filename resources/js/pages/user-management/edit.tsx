import { Head } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import Heading from '@/components/heading';
import EditUserForm from '@/components/user-management/edit-user-form';
import RecordViewers from '@/components/user-management/record-viewers';
import AppLayout from '@/layouts/app-layout';
import { edit, index } from '@/routes/users';
import type { BreadcrumbItem, PageProps } from '@/types';
import type { UserFormPageData } from '@/types/generated';

export default function EditUser({ user, roles }: PageProps<UserFormPageData>) {
    const { t } = useLaravelReactI18n();

    if (!user) return null;

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: t('users.breadcrumb.index'),
            href: index().url,
        },
        {
            title: t('users.breadcrumb.edit', { name: user.name }),
            href: edit(user.id).url,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={t('users.form.edit_title', { name: user.name })} />

            <div className="mx-auto w-full max-w-2xl space-y-6 p-4">
                <Heading
                    title={t('users.form.edit_title', { name: user.name })}
                    description={t('users.form.edit_description')}
                />

                <RecordViewers channel={`viewing.user.${user.id}`} />

                <EditUserForm user={user} roles={roles} />
            </div>
        </AppLayout>
    );
}
