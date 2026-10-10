import { Head } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import ForgotPasswordForm from '@/components/auth/forgot-password-form';
import AuthLayout from '@/layouts/auth-layout';
import type { PageProps } from '@/types';
import type { ForgotPasswordPageData } from '@/types/generated';

export default function ForgotPassword({
    status,
}: PageProps<ForgotPasswordPageData>) {
    const { t } = useLaravelReactI18n();
    return (
        <AuthLayout
            title={t('auth.forgot_password.title')}
            description={t('auth.forgot_password.description')}
        >
            <Head title={t('auth.forgot_password.head')} />
            <ForgotPasswordForm status={status} />
        </AuthLayout>
    );
}
