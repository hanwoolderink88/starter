import { Head } from '@inertiajs/react';
import RegisterForm from '@/components/auth/register-form';
import AuthLayout from '@/layouts/auth-layout';
import type { PageProps } from '@/types';
import type { RegisterPageData } from '@/types/generated';

export default function Register({
    passwordRules,
}: PageProps<RegisterPageData>) {
    return (
        <AuthLayout
            title="Create an account"
            description="Enter your details below to create your account"
        >
            <Head title="Register" />
            <RegisterForm passwordRules={passwordRules} />
        </AuthLayout>
    );
}
