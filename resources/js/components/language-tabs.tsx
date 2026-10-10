import { router, usePage } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { Languages } from 'lucide-react';
import { useImpersonation } from '@/hooks/use-impersonation';
import { cn } from '@/lib/utils';
import { update } from '@/routes/locale';

/**
 * The interface language, saved on the account so mails follow it too. The
 * server answers with a full page load, so every loaded translation switches.
 * While impersonating, the language belongs to the impersonated user.
 */
export default function LanguageTabs() {
    const { t } = useLaravelReactI18n();
    const { locale, supportedLocales } = usePage().props;
    const { isImpersonating } = useImpersonation();

    function choose(value: string) {
        if (value !== locale) {
            router.patch(update().url, { locale: value });
        }
    }

    return (
        <div className="space-y-2">
            <div
                role="radiogroup"
                aria-label={t('settings.language.title')}
                className="inline-flex gap-1 rounded-lg bg-muted p-1"
            >
                {supportedLocales.map((value) => (
                    <button
                        key={value}
                        type="button"
                        role="radio"
                        aria-checked={locale === value}
                        lang={value}
                        disabled={isImpersonating}
                        onClick={() => choose(value)}
                        className={cn(
                            'flex items-center rounded-md px-3.5 py-1.5 text-sm transition-colors disabled:cursor-not-allowed disabled:opacity-50',
                            locale === value
                                ? 'bg-white shadow-xs dark:bg-accent dark:text-accent-foreground'
                                : 'text-muted-foreground hover:bg-accent/60 hover:text-foreground dark:hover:bg-accent/60',
                        )}
                    >
                        <Languages className="-ml-1 size-4" />
                        <span className="ml-1.5">
                            {t(`settings.language.options.${value}`)}
                        </span>
                    </button>
                ))}
            </div>
            {isImpersonating && (
                <p className="text-sm text-muted-foreground">
                    {t('settings.language.blocked')}
                </p>
            )}
        </div>
    );
}
