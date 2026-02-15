import { Head } from '@inertiajs/react';
import TwoFactorChallengeForm from '@/components/auth/two-factor-challenge-form';

export default function TwoFactorChallenge() {
    return (
        <>
            <Head title="Two-Factor Authentication" />
            <TwoFactorChallengeForm />
        </>
    );
}
