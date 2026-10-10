import { useForm } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { Monitor, Smartphone } from 'lucide-react';
import { useRef, useState } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { destroy } from '@/routes/sessions';
import type { SessionsPageData } from '@/types/generated';

/**
 * Where the user is logged in, and a way to log out everywhere else. That
 * asks for the password, so someone at an unlocked laptop cannot lock the
 * owner out of their other devices.
 */
export default function BrowserSessions({
    sessions,
    available,
}: SessionsPageData) {
    const { t } = useLaravelReactI18n();
    const [open, setOpen] = useState(false);
    const passwordInput = useRef<HTMLInputElement>(null);
    const {
        data,
        setData,
        delete: destroySessions,
        processing,
        errors,
        reset,
    } = useForm({ password: '' });

    function submit(e: React.FormEvent) {
        e.preventDefault();

        destroySessions(destroy().url, {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                setOpen(false);
            },
            onError: () => passwordInput.current?.focus(),
        });
    }

    return (
        <div className="space-y-6">
            <Heading
                variant="small"
                title={t('settings.sessions.heading')}
                description={t('settings.sessions.description')}
            />

            {!available ? (
                <p className="text-sm text-muted-foreground">
                    {t('settings.sessions.unavailable')}
                </p>
            ) : (
                <ul className="space-y-4">
                    {sessions.map((session) => {
                        const Icon = session.isDesktop ? Monitor : Smartphone;

                        return (
                            <li
                                key={`${session.lastActiveAt}-${session.ipAddress}-${session.browser}`}
                                className="flex items-center gap-3"
                            >
                                <Icon
                                    aria-hidden
                                    className="size-8 shrink-0 text-muted-foreground"
                                />
                                <div className="min-w-0 text-sm">
                                    <p className="font-medium">
                                        {session.browser ??
                                            t(
                                                'settings.sessions.unknown_browser',
                                            )}
                                        {' · '}
                                        {session.platform ??
                                            t(
                                                'settings.sessions.unknown_platform',
                                            )}
                                    </p>
                                    <p className="text-muted-foreground">
                                        {session.ipAddress ??
                                            t('settings.sessions.unknown_ip')}
                                        {' · '}
                                        {session.isCurrentDevice ? (
                                            <span className="font-medium text-success">
                                                {t(
                                                    'settings.sessions.this_device',
                                                )}
                                            </span>
                                        ) : (
                                            <time
                                                dateTime={session.lastActiveAt}
                                            >
                                                {t(
                                                    'settings.sessions.last_active',
                                                    {
                                                        time: session.lastActiveDisplay,
                                                    },
                                                )}
                                            </time>
                                        )}
                                    </p>
                                </div>
                            </li>
                        );
                    })}
                </ul>
            )}

            <Dialog
                open={open}
                onOpenChange={(value) => {
                    setOpen(value);
                    if (!value) {
                        reset();
                    }
                }}
            >
                <DialogTrigger asChild>
                    <Button variant="outline">
                        {t('settings.sessions.logout_others')}
                    </Button>
                </DialogTrigger>
                <DialogContent>
                    <form onSubmit={submit} className="grid gap-4">
                        <DialogTitle>
                            {t('settings.sessions.dialog_title')}
                        </DialogTitle>
                        <DialogDescription>
                            {t('settings.sessions.dialog_description')}
                        </DialogDescription>
                        <div className="grid gap-2">
                            <Label
                                htmlFor="sessions-password"
                                className="sr-only"
                            >
                                {t('settings.sessions.password')}
                            </Label>
                            <PasswordInput
                                id="sessions-password"
                                ref={passwordInput}
                                value={data.password}
                                onChange={(e) =>
                                    setData('password', e.target.value)
                                }
                                placeholder={t('settings.sessions.password')}
                                autoComplete="current-password"
                                autoFocus
                            />
                            <InputError message={errors.password} />
                        </div>
                        <DialogFooter className="gap-2">
                            <DialogClose asChild>
                                <Button type="button" variant="secondary">
                                    {t('settings.sessions.cancel')}
                                </Button>
                            </DialogClose>
                            <Button disabled={processing}>
                                {t('settings.sessions.confirm')}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </div>
    );
}
