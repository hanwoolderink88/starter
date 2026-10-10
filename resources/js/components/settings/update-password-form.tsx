import { Form } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { useRef } from 'react';
import PasswordController from '@/actions/App/Features/Settings/Controllers/PasswordController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';

type UpdatePasswordFormProps = {
    passwordRules: string;
};

export default function UpdatePasswordForm({
    passwordRules,
}: UpdatePasswordFormProps) {
    const { t } = useLaravelReactI18n();
    const passwordInput = useRef<HTMLInputElement>(null);
    const currentPasswordInput = useRef<HTMLInputElement>(null);

    return (
        <div className="space-y-6">
            <Heading
                variant="small"
                title={t('settings.password.heading')}
                description={t('settings.password.description')}
            />

            <Form
                {...PasswordController.update.form()}
                options={{
                    preserveScroll: true,
                }}
                resetOnError={[
                    'password',
                    'password_confirmation',
                    'current_password',
                ]}
                resetOnSuccess
                onError={(errors) => {
                    if (errors.password) {
                        passwordInput.current?.focus();
                    }

                    if (errors.current_password) {
                        currentPasswordInput.current?.focus();
                    }
                }}
                className="space-y-6"
            >
                {({ errors, processing }) => (
                    <>
                        <div className="grid gap-2">
                            <Label htmlFor="current_password">
                                {t('settings.password.current')}
                            </Label>

                            <PasswordInput
                                id="current_password"
                                ref={currentPasswordInput}
                                name="current_password"
                                className="mt-1 block w-full"
                                autoComplete="current-password"
                                placeholder={t('settings.password.current')}
                            />

                            <InputError message={errors.current_password} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="password">
                                {t('settings.password.new')}
                            </Label>

                            <PasswordInput
                                id="password"
                                ref={passwordInput}
                                name="password"
                                className="mt-1 block w-full"
                                autoComplete="new-password"
                                placeholder={t('settings.password.new')}
                                passwordrules={passwordRules}
                            />

                            <InputError message={errors.password} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="password_confirmation">
                                {t('settings.password.confirm')}
                            </Label>

                            <PasswordInput
                                id="password_confirmation"
                                name="password_confirmation"
                                className="mt-1 block w-full"
                                autoComplete="new-password"
                                placeholder={t('settings.password.confirm')}
                                passwordrules={passwordRules}
                            />

                            <InputError
                                message={errors.password_confirmation}
                            />
                        </div>

                        <div className="flex items-center gap-4">
                            <Button
                                disabled={processing}
                                data-test="update-password-button"
                            >
                                {t('settings.password.save')}
                            </Button>
                        </div>
                    </>
                )}
            </Form>
        </div>
    );
}
