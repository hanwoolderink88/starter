import { Head } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import RegisterForm from '@/components/auth/register-form';
import AuthLayout from '@/layouts/auth-layout';
import type { PageProps } from '@/types';
import type { RegisterPageData } from '@/types/generated';

export default function Register({
    passwordRules,
}: PageProps<RegisterPageData>) {
    const { t } = useLaravelReactI18n();
    return (
        <AuthLayout
            title={t('auth.register.title')}
            description={t('auth.register.description')}
        >
            <Head title={t('auth.register.head')} />
            <RegisterForm passwordRules={passwordRules} />
        </AuthLayout>
    );
}
