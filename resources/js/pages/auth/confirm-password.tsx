import { Head } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import ConfirmPasswordForm from '@/components/auth/confirm-password-form';
import AuthLayout from '@/layouts/auth-layout';

export default function ConfirmPassword() {
    const { t } = useLaravelReactI18n();
    return (
        <AuthLayout
            title={t('auth.confirm_password.title')}
            description={t('auth.confirm_password.description')}
        >
            <Head title={t('auth.confirm_password.head')} />
            <ConfirmPasswordForm />
        </AuthLayout>
    );
}
