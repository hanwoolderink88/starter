import { Head, Link, router } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { Pencil } from 'lucide-react';
import { toast } from 'sonner';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import RecordViewers from '@/components/user-management/record-viewers';
import { useRealtimeResource } from '@/hooks/use-realtime-resource';
import AppLayout from '@/layouts/app-layout';
import { edit, index, show } from '@/routes/users';
import type { BreadcrumbItem, PageProps } from '@/types';
import { ResourceAction, type ShowUserPageData } from '@/types/generated';

export default function ShowUser({
    user,
    canUpdate,
}: PageProps<ShowUserPageData>) {
    const { t } = useLaravelReactI18n();

    // Live, no-confirm updates: reload the record when it changes, redirect away
    // if it's deleted. Read-only page, so there's no unsaved state to protect.
    useRealtimeResource({
        channel: `user.${user.id}`,
        event: '.UserChanged',
        only: ['user'],
        mode: 'auto',
        describe: (event) =>
            t('users.realtime.record_updated', { actor: event.actorName }),
        onChange: (event) => {
            if (event.action === ResourceAction.Deleted) {
                toast.warning(
                    t('users.realtime.record_deleted', {
                        actor: event.actorName,
                    }),
                );
                router.visit(index().url);

                return true;
            }
        },
    });

    const breadcrumbs: BreadcrumbItem[] = [
        { title: t('users.breadcrumb.index'), href: index().url },
        {
            title: t('users.breadcrumb.show', { name: user.name }),
            href: show(user.id).url,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={user.name} />

            <div className="mx-auto w-full max-w-2xl space-y-6 p-4">
                <div className="flex items-center justify-between gap-4">
                    <Heading
                        title={user.name}
                        description={t('users.show.description')}
                    />
                    {canUpdate && (
                        <Button asChild>
                            <Link
                                href={edit(user.id)}
                                prefetch
                                cacheTags={[`user.${user.id}`]}
                            >
                                <Pencil className="mr-2 size-4" />
                                {t('users.show.edit')}
                            </Link>
                        </Button>
                    )}
                </div>

                <RecordViewers channel={`viewing.user.${user.id}`} />

                <Card>
                    <CardContent className="grid gap-4 sm:grid-cols-2">
                        <Field label={t('users.columns.email')}>
                            {user.email}
                        </Field>
                        <Field label={t('users.columns.role')}>
                            <Badge
                                variant={
                                    user.role === 'super-admin'
                                        ? 'default'
                                        : 'secondary'
                                }
                            >
                                {user.role}
                            </Badge>
                        </Field>
                        <Field label={t('users.columns.status')}>
                            <Badge
                                variant={
                                    user.has_password ? 'success' : 'warning'
                                }
                            >
                                {user.has_password
                                    ? t('users.status.active')
                                    : t('users.status.invited')}
                            </Badge>
                        </Field>
                        <Field label={t('users.show.member_since')}>
                            {user.created_at_display}
                        </Field>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}

function Field({
    label,
    children,
}: {
    label: string;
    children: React.ReactNode;
}) {
    return (
        <div className="space-y-1">
            <dt className="text-sm text-muted-foreground">{label}</dt>
            <dd className="font-medium">{children}</dd>
        </div>
    );
}
