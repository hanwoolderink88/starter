import { Head } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import VerifyEmailForm from '@/components/auth/verify-email-form';
import AuthLayout from '@/layouts/auth-layout';
import type { PageProps } from '@/types';
import type { VerifyEmailPageData } from '@/types/generated';

export default function VerifyEmail({
    status,
}: PageProps<VerifyEmailPageData>) {
    const { t } = useLaravelReactI18n();
    return (
        <AuthLayout
            title={t('auth.verify_email.title')}
            description={t('auth.verify_email.description')}
        >
            <Head title={t('auth.verify_email.head')} />
            <VerifyEmailForm status={status} />
        </AuthLayout>
    );
}
