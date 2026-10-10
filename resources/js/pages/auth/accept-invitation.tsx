import { Head, useForm } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import type { FormEventHandler } from 'react';
import StoreAcceptInvitation from '@/actions/App/Features/UserManagement/Controllers/StoreAcceptInvitationController';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
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
    const { t } = useLaravelReactI18n();
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
            title={t('auth.invitation.accept.title', { name: user.name })}
            description={t('auth.invitation.accept.description')}
        >
            <Head title={t('auth.invitation.accept.head')} />

            <form onSubmit={submit} className="space-y-4">
                <div className="space-y-2">
                    <Label htmlFor="password">
                        {t('auth.fields.password')}
                    </Label>
                    <PasswordInput
                        id="password"
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
                        {t('auth.fields.password_confirmation')}
                    </Label>
                    <PasswordInput
                        id="password_confirmation"
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
                        {processing
                            ? t('auth.invitation.accept.processing')
                            : t('auth.invitation.accept.submit')}
                    </Button>
                </div>
            </form>
        </AuthLayout>
    );
}
