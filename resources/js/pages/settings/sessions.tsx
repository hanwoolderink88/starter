import { Head } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import BrowserSessions from '@/components/settings/browser-sessions';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { index } from '@/routes/sessions';
import type { BreadcrumbItem, PageProps } from '@/types';
import type { SessionsPageData } from '@/types/generated';

export default function Sessions({
    sessions,
    available,
}: PageProps<SessionsPageData>) {
    const { t } = useLaravelReactI18n();

    const breadcrumbs: BreadcrumbItem[] = [
        { title: t('settings.sessions.title'), href: index().url },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={t('settings.sessions.title')} />

            <h1 className="sr-only">{t('settings.sessions.title')}</h1>

            <SettingsLayout>
                <BrowserSessions sessions={sessions} available={available} />
            </SettingsLayout>
        </AppLayout>
    );
}
