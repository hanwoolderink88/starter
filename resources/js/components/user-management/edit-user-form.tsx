import { router, useForm, usePage } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';
import ConflictBanner from '@/components/conflict-banner';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import RoleChecklist from '@/components/user-management/role-checklist';
import { useRealtimeResource } from '@/hooks/use-realtime-resource';
import { index, update } from '@/routes/users';
import { destroy as revokeRole } from '@/routes/users/roles';
import {
    ResourceAction,
    type AssignableRoleData,
    type UserFormPageData,
    type UserManagementData,
} from '@/types/generated';

type Fields = {
    name: string;
    email: string;
    role_ids: number[];
    version: number;
};

/** The form fields for a user: their active roles are the chosen ones. */
function formFields(
    user: UserManagementData,
    roles: AssignableRoleData[],
    roleIds: number[],
): Fields {
    const activeIds = roles
        .filter((role) => role.isActive)
        .map((role) => role.id);

    return {
        name: user.name,
        email: user.email,
        role_ids: roleIds.filter((id) => activeIds.includes(id)),
        // The version this edit is based on; a stale one is refused.
        version: user.version,
    };
}

export default function EditUserForm({
    user,
    roles,
    roleIds,
    canAssignRoles,
    isLastAdministrator,
}: {
    user: UserManagementData;
} & Pick<
    UserFormPageData,
    'roles' | 'roleIds' | 'canAssignRoles' | 'isLastAdministrator'
>) {
    const { t } = useLaravelReactI18n();
    const pageErrors = usePage().props.errors;
    const [revokingId, setRevokingId] = useState<number | null>(null);
    const { data, setData, put, processing, errors, transform, clearErrors } =
        useForm<Fields>(formFields(user, roles, roleIds));

    // The last administrator keeps the administrator role.
    const lockedRoleIds = isLastAdministrator
        ? roles.filter((role) => role.isProtected).map((role) => role.id)
        : [];

    // After an "ask" reload pulls fresh data into the `user` prop, sync the
    // form fields to it. A refused save comes back with the current user too:
    // then the input and the version it was based on stay, until the
    // conflict banner's reload.
    useEffect(() => {
        if (Object.keys(pageErrors).length > 0) {
            return;
        }

        setData(formFields(user, roles, roleIds));
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [user, roleIds]);

    function reloadAfterConflict() {
        router.reload({
            only: ['user', 'roles', 'roleIds', 'isLastAdministrator'],
            onSuccess: (page) => {
                const props = page.props as unknown as UserFormPageData;

                if (props.user) {
                    setData(formFields(props.user, props.roles, props.roleIds));
                }

                clearErrors('version');
            },
        });
    }

    function revoke(role: AssignableRoleData) {
        router.delete(revokeRole({ user: user.id, role: role.id }).url, {
            preserveScroll: true,
            only: ['roleIds', 'roles', 'errors'],
            onStart: () => setRevokingId(role.id),
            onFinish: () => setRevokingId(null),
        });
    }

    // React to concurrent changes to this exact user by someone else.
    useRealtimeResource({
        channel: `user.${user.id}`,
        event: '.UserChanged',
        only: ['user', 'roleIds', 'isLastAdministrator'],
        mode: 'ask',
        describe: (_event, actor) =>
            t('users.realtime.record_updated', { actor }),
        onChange: (event, actor) => {
            if (event.action === ResourceAction.Deleted) {
                toast.warning(t('users.realtime.record_deleted', { actor }));
                router.visit(index().url);

                return true;
            }
        },
    });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        // Without the right to change roles, the form leaves them unchanged.
        transform(({ role_ids, ...fields }) =>
            canAssignRoles ? { ...fields, role_ids } : fields,
        );
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
                    {errors.version && (
                        <ConflictBanner
                            message={errors.version}
                            copyText={[
                                `${t('users.form.name')}: ${data.name}`,
                                `${t('users.form.email')}: ${data.email}`,
                                `${t('users.form.roles')}: ${roles
                                    .filter((role) =>
                                        data.role_ids.includes(role.id),
                                    )
                                    .map((role) => role.name)
                                    .join(', ')}`,
                            ].join('\n')}
                            onReload={reloadAfterConflict}
                        />
                    )}

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
                        assignedIds={roleIds}
                        lockedIds={lockedRoleIds}
                        readOnly={!canAssignRoles}
                        onChange={(ids) => setData('role_ids', ids)}
                        onRevoke={revoke}
                        revokingId={revokingId}
                        error={errors.role_ids}
                    />

                    <Button disabled={processing}>
                        {t('users.form.update')}
                    </Button>
                </form>
            </CardContent>
        </Card>
    );
}
