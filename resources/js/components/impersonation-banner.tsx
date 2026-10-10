import { usePage } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { UserCheck } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { useImpersonation } from '@/hooks/use-impersonation';

/**
 * Shown on every page while impersonating, so it is never forgotten that
 * everything done here is done as someone else.
 */
export default function ImpersonationBanner() {
    const { t } = useLaravelReactI18n();
    const { auth } = usePage().props;
    const { isImpersonating, stop } = useImpersonation();

    if (!isImpersonating || !auth.user) {
        return null;
    }

    return (
        <div
            role="status"
            className="flex flex-wrap items-center justify-between gap-2 bg-primary px-4 py-2 text-sm text-primary-foreground"
        >
            <p className="flex items-center gap-2">
                <UserCheck className="size-4 shrink-0" aria-hidden />
                {t('users.impersonation.banner', { name: auth.user.name })}
            </p>
            <Button size="sm" variant="secondary" onClick={stop}>
                {t('users.impersonation.stop')}
            </Button>
        </div>
    );
}
