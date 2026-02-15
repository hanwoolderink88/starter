import { Head } from '@inertiajs/react';
import VerifyEmailForm from '@/components/auth/verify-email-form';
import AuthLayout from '@/layouts/auth-layout';

export default function VerifyEmail({
    status,
}: App.Features.Auth.Data.VerifyEmailPageData) {
    return (
        <AuthLayout
            title="Verify email"
            description="Please verify your email address by clicking on the link we just emailed to you."
        >
            <Head title="Email verification" />
            <VerifyEmailForm status={status} />
        </AuthLayout>
    );
}
