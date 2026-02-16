import { Head } from '@inertiajs/react';
import VerifyEmailForm from '@/components/auth/verify-email-form';
import AuthLayout from '@/layouts/auth-layout';
import type { PageProps } from '@/types';
import type { VerifyEmailPageData } from '@/types/generated';

export default function VerifyEmail({
    status,
}: PageProps<VerifyEmailPageData>) {
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
