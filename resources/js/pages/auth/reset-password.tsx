import { Head } from '@inertiajs/react';
import ResetPasswordForm from '@/components/auth/reset-password-form';
import AuthLayout from '@/layouts/auth-layout';
import type { PageProps } from '@/types';
import type { ResetPasswordPageData } from '@/types/generated';

export default function ResetPassword({
    token,
    email,
}: PageProps<ResetPasswordPageData>) {
    return (
        <AuthLayout
            title="Reset password"
            description="Please enter your new password below"
        >
            <Head title="Reset password" />
            <ResetPasswordForm token={token} email={email} />
        </AuthLayout>
    );
}
