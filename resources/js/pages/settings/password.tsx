import { Head } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import UpdatePasswordForm from '@/components/settings/update-password-form';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { edit } from '@/routes/user-password';
import type { BreadcrumbItem, PageProps } from '@/types';
import type { PasswordPageData } from '@/types/generated';

export default function Password({
    passwordRules,
}: PageProps<PasswordPageData>) {
    const { t } = useLaravelReactI18n();

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: t('settings.password.title'),
            href: edit().url,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={t('settings.password.title')} />

            <h1 className="sr-only">{t('settings.password.title')}</h1>

            <SettingsLayout>
                <UpdatePasswordForm passwordRules={passwordRules} />
            </SettingsLayout>
        </AppLayout>
    );
}
