import { Head } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import ActivityTimeline from '@/components/activity/activity-timeline';
import RoleActions from '@/components/roles/role-actions';
import RoleForm from '@/components/roles/role-form';
import RoleStatusLine from '@/components/roles/role-status-line';
import AppLayout from '@/layouts/app-layout';
import { edit, index } from '@/routes/roles';
import type { BreadcrumbItem, PageProps } from '@/types';
import type { RoleFormPageData } from '@/types/generated';

export default function EditRole({
    role,
    permissionGroups,
    rolePermissions,
    activity,
    holders,
    fields,
}: PageProps<RoleFormPageData>) {
    const { t } = useLaravelReactI18n();

    if (!role) return null;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: t('roles.breadcrumb.index'), href: index().url },
        { title: role.name, href: edit(role.id).url },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={role.name} />

            <div className="mx-auto w-full max-w-5xl space-y-8 p-4">
                <RoleForm
                    role={role}
                    permissionGroups={permissionGroups}
                    permissions={rolePermissions}
                    fields={fields}
                    actions={<RoleActions role={role} holders={holders} />}
                    header={
                        <div className="min-w-0 space-y-1.5">
                            <h2 className="text-xl font-semibold tracking-tight break-words">
                                {role.name}
                            </h2>
                            <RoleStatusLine role={role} />
                        </div>
                    }
                />

                {activity !== null && (
                    <ActivityTimeline
                        activity={activity}
                        subjectType="role"
                        subjectId={role.id}
                    />
                )}
            </div>
        </AppLayout>
    );
}
