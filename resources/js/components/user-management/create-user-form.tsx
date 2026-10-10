import { useForm } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import RoleChecklist from '@/components/user-management/role-checklist';
import { store } from '@/routes/users';
import type { UserFormPageData } from '@/types/generated';

export default function CreateUserForm({
    roles,
    canAssignRoles,
}: Pick<UserFormPageData, 'roles' | 'canAssignRoles'>) {
    const { t } = useLaravelReactI18n();
    const { data, setData, post, processing, errors } = useForm<{
        name: string;
        email: string;
        role_ids: number[];
    }>({
        name: '',
        email: '',
        role_ids: [],
    });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        post(store().url);
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

                    <RoleChecklist
                        roles={roles}
                        selectedIds={data.role_ids}
                        readOnly={!canAssignRoles}
                        onChange={(ids) => setData('role_ids', ids)}
                        error={errors.role_ids}
                    />

                    <Button disabled={processing}>
                        {t('users.form.create')}
                    </Button>
                </form>
            </CardContent>
        </Card>
    );
}
