import { Head } from '@inertiajs/react';
import ForgotPasswordForm from '@/components/auth/forgot-password-form';
import AuthLayout from '@/layouts/auth-layout';
import type { PageProps } from '@/types';
import type { ForgotPasswordPageData } from '@/types/generated';

export default function ForgotPassword({
    status,
}: PageProps<ForgotPasswordPageData>) {
    return (
        <AuthLayout
            title="Forgot password"
            description="Enter your email to receive a password reset link"
        >
            <Head title="Forgot password" />
            <ForgotPasswordForm status={status} />
        </AuthLayout>
    );
}
