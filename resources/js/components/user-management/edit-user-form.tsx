import { router, useForm } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { useEffect } from 'react';
import { toast } from 'sonner';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useRealtimeResource } from '@/hooks/use-realtime-resource';
import { index, update } from '@/routes/users';
import {
    ResourceAction,
    type UserFormPageData,
    type UserManagementData,
} from '@/types/generated';

export default function EditUserForm({
    user,
    roles,
}: {
    user: UserManagementData;
    roles: UserFormPageData['roles'];
}) {
    const { t } = useLaravelReactI18n();
    const { data, setData, put, processing, errors } = useForm({
        name: user.name,
        email: user.email,
        role: user.role,
    });

    // After an "ask" reload pulls fresh data into the `user` prop, sync the
    // form fields to it. This only fires when the prop actually changes — i.e.
    // when the editor explicitly chose to load someone else's update.
    useEffect(() => {
        setData({ name: user.name, email: user.email, role: user.role });
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [user]);

    // React to concurrent changes to this exact user by someone else.
    useRealtimeResource({
        channel: `user.${user.id}`,
        event: '.UserChanged',
        only: ['user'],
        mode: 'ask',
        describe: (event) =>
            t('users.realtime.record_updated', { actor: event.actorName }),
        onChange: (event) => {
            if (event.action === ResourceAction.Deleted) {
                toast.warning(
                    t('users.realtime.record_deleted', {
                        actor: event.actorName,
                    }),
                );
                router.visit(index().url);

                return true;
            }
        },
    });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        // Drop this user's prefetched edit page so re-opening it after the
        // redirect refetches the new values instead of serving the stale cache.
        put(update(user.id).url, {
            invalidateCacheTags: [`user.${user.id}`],
        });
    }

    return (
        <Card>
            <CardContent>
                <form onSubmit={submit} className="space-y-6">
                    <div className="grid gap-2">
                        <Label htmlFor="name">{t('users.form.name')}</Label>
                        <Input
                            id="name"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            required
                            autoComplete="name"
                            placeholder={t('users.form.name_placeholder')}
                        />
                        <InputError message={errors.name} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="email">{t('users.form.email')}</Label>
                        <Input
                            id="email"
                            type="email"
                            value={data.email}
                            onChange={(e) => setData('email', e.target.value)}
                            required
                            autoComplete="email"
                            placeholder={t('users.form.email_placeholder')}
                        />
                        <InputError message={errors.email} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="role">{t('users.form.role')}</Label>
                        <Select
                            value={data.role}
                            onValueChange={(value) => setData('role', value)}
                        >
                            <SelectTrigger>
                                <SelectValue
                                    placeholder={t(
                                        'users.form.role_placeholder',
                                    )}
                                />
                            </SelectTrigger>
                            <SelectContent>
                                {Object.entries(roles).map(([value, label]) => (
                                    <SelectItem key={value} value={value}>
                                        {label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.role} />
                    </div>

                    <Button disabled={processing}>
                        {t('users.form.update')}
                    </Button>
                </form>
            </CardContent>
        </Card>
    );
}
