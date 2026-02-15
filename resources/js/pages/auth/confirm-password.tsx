import { Head } from '@inertiajs/react';
import ConfirmPasswordForm from '@/components/auth/confirm-password-form';
import AuthLayout from '@/layouts/auth-layout';

export default function ConfirmPassword() {
    return (
        <AuthLayout
            title="Confirm your password"
            description="This is a secure area of the application. Please confirm your password before continuing."
        >
            <Head title="Confirm password" />
            <ConfirmPasswordForm />
        </AuthLayout>
    );
}
