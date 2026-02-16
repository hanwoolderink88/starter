import { Head, useForm } from '@inertiajs/react';
import type { FormEventHandler } from 'react';
import StoreAcceptInvitation from '@/actions/App/Features/UserManagement/Controllers/StoreAcceptInvitationController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/auth-layout';

interface Props {
    user: {
        id: number;
        name: string;
        email: string;
    };
}

export default function AcceptInvitation({ user }: Props) {
    const { data, setData, post, processing, errors, reset } = useForm({
        password: '',
        password_confirmation: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(StoreAcceptInvitation.url({ user: user.id }), {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    return (
        <AuthLayout
            title={`Welcome, ${user.name}!`}
            description="Please set a password to complete your account setup."
        >
            <Head title="Accept Invitation" />

            <form onSubmit={submit} className="space-y-4">
                <div className="space-y-2">
                    <Label htmlFor="password">Password</Label>
                    <Input
                        id="password"
                        type="password"
                        name="password"
                        value={data.password}
                        className="block w-full"
                        autoComplete="new-password"
                        onChange={(e) => setData('password', e.target.value)}
                        required
                    />
                    <InputError message={errors.password} className="mt-2" />
                </div>

                <div className="space-y-2">
                    <Label htmlFor="password_confirmation">
                        Confirm Password
                    </Label>
                    <Input
                        id="password_confirmation"
                        type="password"
                        name="password_confirmation"
                        value={data.password_confirmation}
                        className="block w-full"
                        autoComplete="new-password"
                        onChange={(e) =>
                            setData('password_confirmation', e.target.value)
                        }
                        required
                    />
                    <InputError
                        message={errors.password_confirmation}
                        className="mt-2"
                    />
                </div>

                <div className="mt-4 flex items-center justify-end">
                    <Button className="w-full" disabled={processing}>
                        {processing ? 'Setting Password...' : 'Complete Setup'}
                    </Button>
                </div>
            </form>
        </AuthLayout>
    );
}
