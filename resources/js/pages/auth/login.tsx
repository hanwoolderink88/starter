import { Head } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import LoginForm from '@/components/auth/login-form';
import AuthLayout from '@/layouts/auth-layout';
import type { PageProps } from '@/types';
import type { LoginPageData } from '@/types/generated';

export default function Login({
    status,
    canResetPassword,
    canRegister,
}: PageProps<LoginPageData>) {
    const { t } = useLaravelReactI18n();
    return (
        <AuthLayout
            title={t('auth.login.title')}
            description={t('auth.login.description')}
        >
            <Head title={t('auth.login.head')} />
            <LoginForm
                status={status}
                canResetPassword={canResetPassword}
                canRegister={canRegister}
            />
        </AuthLayout>
    );
}
