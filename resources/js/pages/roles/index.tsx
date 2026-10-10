import { Head, Link } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { Plus } from 'lucide-react';
import Heading from '@/components/heading';
import RolesTable from '@/components/roles/roles-table';
import { Button } from '@/components/ui/button';
import { useRealtimeResource } from '@/hooks/use-realtime-resource';
import AppLayout from '@/layouts/app-layout';
import { create, index } from '@/routes/roles';
import type { BreadcrumbItem, PageProps } from '@/types';
import type { RolesPageData } from '@/types/generated';

export default function RolesIndex({
    roles,
    permissionTotal,
    inactiveCount,
}: PageProps<RolesPageData>) {
    const { t, tChoice } = useLaravelReactI18n();

    const breadcrumbs: BreadcrumbItem[] = [
        { title: t('roles.breadcrumb.index'), href: index().url },
    ];

    // Keep the list in sync when anyone else creates, changes or deletes a
    // role, and drop the stale prefetched edit page of that role.
    useRealtimeResource({
        channel: 'roles',
        event: '.RoleChanged',
        only: ['roles', 'inactiveCount'],
        mode: 'auto',
        invalidateTags: (event) => [`role.${event.id}`],
        describe: (event, actor) =>
            t(`roles.realtime.${event.action}`, { name: event.label, actor }),
    });

    const summary = [
        tChoice('roles.summary.total', roles.length),
        inactiveCount > 0
            ? t('roles.summary.inactive', { count: inactiveCount })
            : null,
    ]
        .filter(Boolean)
        .join(' · ');

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={t('roles.title')} />

            <div className="mx-auto w-full max-w-5xl space-y-6 p-4">
                <div className="flex items-center justify-between gap-4">
                    <Heading
                        title={t('roles.title')}
                        description={`${t('roles.description')} · ${summary}`}
                    />
                    <Button asChild>
                        <Link href={create()} prefetch>
                            <Plus className="mr-2 size-4" />
                            {t('roles.actions.create')}
                        </Link>
                    </Button>
                </div>

                <RolesTable roles={roles} permissionTotal={permissionTotal} />
            </div>
        </AppLayout>
    );
}
