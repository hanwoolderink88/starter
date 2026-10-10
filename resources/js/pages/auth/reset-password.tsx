import { Head } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import ResetPasswordForm from '@/components/auth/reset-password-form';
import AuthLayout from '@/layouts/auth-layout';
import type { PageProps } from '@/types';
import type { ResetPasswordPageData } from '@/types/generated';

export default function ResetPassword({
    token,
    email,
    passwordRules,
}: PageProps<ResetPasswordPageData>) {
    const { t } = useLaravelReactI18n();
    return (
        <AuthLayout
            title={t('auth.reset_password.title')}
            description={t('auth.reset_password.description')}
        >
            <Head title={t('auth.reset_password.head')} />
            <ResetPasswordForm
                token={token}
                email={email}
                passwordRules={passwordRules}
            />
        </AuthLayout>
    );
}
