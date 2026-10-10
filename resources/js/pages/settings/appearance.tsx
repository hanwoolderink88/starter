import { Head } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import AppearanceTabs from '@/components/appearance-tabs';
import Heading from '@/components/heading';
import LanguageTabs from '@/components/language-tabs';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { edit as editAppearance } from '@/routes/appearance';
import type { BreadcrumbItem } from '@/types';

export default function Appearance() {
    const { t } = useLaravelReactI18n();

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: t('settings.appearance.title'),
            href: editAppearance().url,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={t('settings.appearance.title')} />

            <h1 className="sr-only">{t('settings.appearance.title')}</h1>

            <SettingsLayout>
                <div className="space-y-6">
                    <Heading
                        variant="small"
                        title={t('settings.appearance.theme.title')}
                        description={t('settings.appearance.theme.description')}
                    />
                    <AppearanceTabs />
                </div>

                <div className="space-y-6">
                    <Heading
                        variant="small"
                        title={t('settings.language.title')}
                        description={t('settings.language.description')}
                    />
                    <LanguageTabs />
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
