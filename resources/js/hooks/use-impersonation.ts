import { router, usePage } from '@inertiajs/react';
import { useCallback } from 'react';
import { stop as stopImpersonating } from '@/routes/impersonation';
import { impersonate } from '@/routes/users';

/**
 * Start or stop impersonating. Both switch the logged-in user, so every
 * prefetched page — rendered for the other user — is dropped first.
 */
export function useImpersonation() {
    const { auth } = usePage().props;

    const start = useCallback((userId: number) => {
        router.flushAll();
        router.post(impersonate(userId).url);
    }, []);

    const stop = useCallback(() => {
        router.flushAll();
        router.post(stopImpersonating().url);
    }, []);

    return {
        impersonatorName: auth.impersonatorName,
        isImpersonating: auth.impersonatorName !== null,
        start,
        stop,
    };
}
