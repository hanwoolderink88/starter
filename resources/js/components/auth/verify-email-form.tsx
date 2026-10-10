import { Form } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { logout } from '@/routes';
import { send } from '@/routes/verification';

type VerifyEmailFormProps = {
    status: string | null;
};

export default function VerifyEmailForm({ status }: VerifyEmailFormProps) {
    const { t } = useLaravelReactI18n();
    return (
        <>
            {status === 'verification-link-sent' && (
                <div className="mb-4 text-center text-sm font-medium text-success">
                    {t('auth.verify_email.link_sent')}
                </div>
            )}

            <Form {...send.form()} className="space-y-6 text-center">
                {({ processing }) => (
                    <>
                        <Button disabled={processing} variant="secondary">
                            {processing && <Spinner />}
                            {t('auth.verify_email.resend')}
                        </Button>

                        <TextLink
                            href={logout()}
                            className="mx-auto block text-sm"
                        >
                            {t('auth.verify_email.logout')}
                        </TextLink>
                    </>
                )}
            </Form>
        </>
    );
}
