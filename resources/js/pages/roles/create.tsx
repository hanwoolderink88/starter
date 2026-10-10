import { Head } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import Heading from '@/components/heading';
import RoleForm from '@/components/roles/role-form';
import AppLayout from '@/layouts/app-layout';
import { create, index } from '@/routes/roles';
import type { BreadcrumbItem, PageProps } from '@/types';
import type { RoleFormPageData } from '@/types/generated';

export default function CreateRole({
    permissionGroups,
}: PageProps<RoleFormPageData>) {
    const { t } = useLaravelReactI18n();

    const breadcrumbs: BreadcrumbItem[] = [
        { title: t('roles.breadcrumb.index'), href: index().url },
        { title: t('roles.breadcrumb.create'), href: create().url },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={t('roles.form.create_title')} />

            <div className="mx-auto w-full max-w-5xl p-4">
                <RoleForm
                    role={null}
                    permissionGroups={permissionGroups}
                    permissions={[]}
                    header={
                        <Heading
                            title={t('roles.form.create_title')}
                            description={t('roles.form.create_description')}
                        />
                    }
                />
            </div>
        </AppLayout>
    );
}
