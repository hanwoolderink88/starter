import { Head } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import DeleteUser from '@/components/delete-user';
import UpdateProfileForm from '@/components/settings/update-profile-form';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { edit } from '@/routes/profile';
import type { BreadcrumbItem, PageProps } from '@/types';
import type { ProfilePageData } from '@/types/generated';

export default function Profile({
    mustVerifyEmail,
    status,
}: PageProps<ProfilePageData>) {
    const { t } = useLaravelReactI18n();

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: t('settings.profile.title'),
            href: edit().url,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={t('settings.profile.title')} />

            <h1 className="sr-only">{t('settings.profile.title')}</h1>

            <SettingsLayout>
                <UpdateProfileForm
                    mustVerifyEmail={mustVerifyEmail}
                    status={status}
                />

                <DeleteUser />
            </SettingsLayout>
        </AppLayout>
    );
}
