import { Link } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { ShieldAlert } from 'lucide-react';
import { useMemo } from 'react';
import type { PropsWithChildren } from 'react';
import Heading from '@/components/heading';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { useImpersonation } from '@/hooks/use-impersonation';
import { cn, toUrl } from '@/lib/utils';
import { edit as editAppearance } from '@/routes/appearance';
import { edit } from '@/routes/profile';
import { index as sessionsIndex } from '@/routes/sessions';
import { show } from '@/routes/two-factor';
import { edit as editPassword } from '@/routes/user-password';
import type { NavItem } from '@/types';

export default function SettingsLayout({ children }: PropsWithChildren) {
    const { t } = useLaravelReactI18n();
    const { isCurrentUrl } = useCurrentUrl();
    const { isImpersonating } = useImpersonation();

    const sidebarNavItems = useMemo<NavItem[]>(
        () => [
            { title: t('settings.nav.profile'), href: edit(), icon: null },
            {
                title: t('settings.nav.password'),
                href: editPassword(),
                icon: null,
            },
            { title: t('settings.nav.two_factor'), href: show(), icon: null },
            {
                title: t('settings.nav.sessions'),
                href: sessionsIndex(),
                icon: null,
            },
            {
                title: t('settings.nav.appearance'),
                href: editAppearance(),
                icon: null,
            },
        ],
        [t],
    );

    // When server-side rendering, we only render the layout on the client...
    if (typeof window === 'undefined') {
        return null;
    }

    return (
        <div className="px-4 py-6">
            <Heading
                title={t('settings.title')}
                description={t('settings.description')}
            />

            <div className="flex flex-col lg:flex-row lg:space-x-12">
                <aside className="w-full max-w-xl lg:w-48">
                    <nav
                        className="flex flex-col space-y-1 space-x-0"
                        aria-label={t('settings.nav.label')}
                    >
                        {sidebarNavItems.map((item, index) => (
                            <Button
                                key={`${toUrl(item.href)}-${index}`}
                                size="sm"
                                variant="ghost"
                                asChild
                                className={cn('w-full justify-start', {
                                    'bg-muted': isCurrentUrl(item.href),
                                })}
                            >
                                <Link href={item.href}>
                                    {item.icon && (
                                        <item.icon className="h-4 w-4" />
                                    )}
                                    {item.title}
                                </Link>
                            </Button>
                        ))}
                    </nav>
                </aside>

                <Separator className="my-6 lg:hidden" />

                <div className="flex-1 md:max-w-2xl">
                    {isImpersonating && (
                        <Alert className="mb-6 max-w-xl">
                            <ShieldAlert />
                            <AlertDescription>
                                {t('users.impersonation.settings_blocked')}
                            </AlertDescription>
                        </Alert>
                    )}
                    <section className="max-w-xl space-y-12">
                        {children}
                    </section>
                </div>
            </div>
        </div>
    );
}
