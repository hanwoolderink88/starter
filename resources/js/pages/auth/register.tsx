import { Head } from '@inertiajs/react';
import RegisterForm from '@/components/auth/register-form';
import AuthLayout from '@/layouts/auth-layout';

export default function Register() {
    return (
        <AuthLayout
            title="Create an account"
            description="Enter your details below to create your account"
        >
            <Head title="Register" />
            <RegisterForm />
        </AuthLayout>
    );
}
