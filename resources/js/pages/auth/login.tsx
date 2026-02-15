import { Head } from '@inertiajs/react';
import LoginForm from '@/components/auth/login-form';
import AuthLayout from '@/layouts/auth-layout';

export default function Login({
    status,
    canResetPassword,
    canRegister,
}: App.Features.Auth.Data.LoginPageData) {
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
