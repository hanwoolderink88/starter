import { Head } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import AuthLayout from '@/layouts/auth-layout';

export default function InvitationExpired() {
    const { t } = useLaravelReactI18n();
    return (
        <AuthLayout
            title={t('auth.invitation.expired.title')}
            description={t('auth.invitation.expired.description')}
        >
            <Head title={t('auth.invitation.expired.head')} />
            <div className="text-center">
                <p className="mt-4 text-muted-foreground">
                    {t('auth.invitation.expired.contact_admin')}
                </p>
            </div>
        </AuthLayout>
    );
}
