import { Head } from '@inertiajs/react';
import LoginForm from '@/components/auth/login-form';
import AuthLayout from '@/layouts/auth-layout';
import type { PageProps } from '@/types';
import type { LoginPageData } from '@/types/generated';

export default function Login({
    status,
    canResetPassword,
    canRegister,
}: PageProps<LoginPageData>) {
    return (
        <AuthLayout
            title="Log in to your account"
            description="Enter your email and password below to log in"
        >
            <Head title="Log in" />
            <LoginForm
                status={status}
                canResetPassword={canResetPassword}
                canRegister={canRegister}
            />
        </AuthLayout>
    );
}
