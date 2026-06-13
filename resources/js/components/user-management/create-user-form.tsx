import { useForm } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
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
import { store } from '@/routes/users';
import type { UserFormPageData } from '@/types/generated';

export default function CreateUserForm({
    roles,
}: Pick<UserFormPageData, 'roles'>) {
    const { t } = useLaravelReactI18n();
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        email: '',
        role: '',
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
                        {t('users.form.create')}
                    </Button>
                </form>
            </CardContent>
        </Card>
    );
}
