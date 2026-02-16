import { Head } from '@inertiajs/react';
import DeleteUser from '@/components/delete-user';
import UpdateProfileForm from '@/components/settings/update-profile-form';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { edit } from '@/routes/profile';
import type { BreadcrumbItem, PageProps } from '@/types';
import type { ProfilePageData } from '@/types/generated';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Profile settings',
        href: edit().url,
    },
];

export default function Profile({
    mustVerifyEmail,
    status,
}: PageProps<ProfilePageData>) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Profile settings" />

            <h1 className="sr-only">Profile Settings</h1>

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
