import { Head } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import TwoFactorChallengeForm from '@/components/auth/two-factor-challenge-form';

export default function TwoFactorChallenge() {
    const { t } = useLaravelReactI18n();
    return (
        <>
            <Head title={t('auth.two_factor.head')} />
            <TwoFactorChallengeForm />
        </>
    );
}
