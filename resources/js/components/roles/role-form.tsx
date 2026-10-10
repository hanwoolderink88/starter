import { router, useForm, usePage } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { Lock } from 'lucide-react';
import { useEffect } from 'react';
import { toast } from 'sonner';
import CollaborativeField from '@/components/collaboration/collaborative-field';
import ConflictBanner from '@/components/conflict-banner';
import InputError from '@/components/input-error';
import PermissionMatrix from '@/components/roles/permission-matrix';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { useCollaborativeFieldSignals } from '@/hooks/use-collaborative-field-signals';
import { useRealtimeResource } from '@/hooks/use-realtime-resource';
import { index, store, update } from '@/routes/roles';
import {
    ResourceAction,
    type CollaborativeFieldData,
    type Permission,
    type PermissionGroupData,
    type RoleData,
} from '@/types/generated';

type Fields = {
    name: string;
    description: string;
    permissions: Permission[];
    version: number;
};

function formFields(role: RoleData | null, permissions: Permission[]): Fields {
    return {
        name: role?.name ?? '',
        description: role?.description ?? '',
        permissions,
        // The version this edit is based on; a stale one is refused.
        version: role?.version ?? 1,
    };
}

type Props = {
    role: RoleData | null;
    permissionGroups: PermissionGroupData[];
    permissions: Permission[];
    /** On the edit page: name and description, saved as they are typed. */
    fields?: Record<string, CollaborativeFieldData> | null;
    /** Rendered above the fields, such as the page title. */
    header: React.ReactNode;
    /** Buttons next to Save on the edit page, such as Deactivate and Delete. */
    actions?: React.ReactNode;
};

/** The props that show a role's name and description. */
const FIELD_PROPS = ['fields', 'role', 'activity'];

/**
 * Create or edit a role: name, description and the permission matrix. When
 * editing, name and description save as they are typed, one editor at a
 * time; Save stores the permissions. The protected administrator role keeps
 * every permission, so its matrix is read-only.
 */
export default function RoleForm({
    role,
    permissionGroups,
    permissions,
    fields,
    header,
    actions,
}: Props) {
    const { t } = useLaravelReactI18n();
    const pageErrors = usePage().props.errors;
    const {
        data,
        setData,
        post,
        put,
        processing,
        errors,
        clearErrors,
        transform,
    } = useForm<Fields>(formFields(role, permissions));

    const isProtected = role?.isProtected ?? false;
    const total = permissionGroups.reduce(
        (count, group) => count + group.permissions.length,
        0,
    );

    // After an "ask" reload brings fresh props, take their values. A refused
    // save comes back with the same role, and then the input stays.
    useEffect(() => {
        if (Object.keys(pageErrors).length > 0) {
            return;
        }

        setData(formFields(role, permissions));
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [permissions]);

    function reloadAfterConflict() {
        router.reload({
            only: ['role', 'rolePermissions', 'activity', 'holders'],
            onSuccess: (page) => {
                const props = page.props as unknown as {
                    role: RoleData;
                    rolePermissions: Permission[];
                };

                setData(formFields(props.role, props.rolePermissions));
                clearErrors('version');
            },
        });
    }

    function submit(e: React.FormEvent) {
        e.preventDefault();

        if (!role) {
            transform(({ name, description, permissions }) => ({
                name,
                description,
                permissions,
            }));
            post(store().url);

            return;
        }

        transform(({ permissions, version }) => ({ permissions, version }));
        put(update(role.id).url, { invalidateCacheTags: [`role.${role.id}`] });
    }

    const permissionErrors = Object.entries(
        errors as Record<string, string | undefined>,
    ).filter(([field]) => field.startsWith('permissions'));

    return (
        <form onSubmit={submit} className="space-y-6">
            {role && <LiveRoleUpdates role={role} />}
            {role && <LiveFieldUpdates role={role} />}

            <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                {header}
                <div className="flex flex-wrap gap-2">
                    {actions}
                    <Button disabled={processing}>
                        {role ? t('roles.form.save') : t('roles.form.create')}
                    </Button>
                </div>
            </div>

            {errors.version && (
                <ConflictBanner
                    message={errors.version}
                    copyText={`${t('roles.form.permissions')}: ${data.permissions.join(', ')}`}
                    onReload={reloadAfterConflict}
                />
            )}

            {isProtected && (
                <div className="flex gap-3 rounded-lg bg-muted p-4">
                    <Lock aria-hidden className="mt-0.5 size-4 shrink-0" />
                    <div className="space-y-1">
                        <p className="font-semibold">
                            {t('roles.form.protected_title')}
                        </p>
                        <p className="text-sm text-muted-foreground">
                            {t('roles.form.protected_body')}
                        </p>
                    </div>
                </div>
            )}

            {fields ? (
                <Card className="grid gap-4 p-5 sm:grid-cols-[1fr_1.5fr]">
                    {fields.name && (
                        <CollaborativeField
                            field={fields.name}
                            label={t('roles.form.name')}
                            placeholder={t('roles.form.name_placeholder')}
                            reloadOnly={FIELD_PROPS}
                        />
                    )}
                    {fields.description && (
                        <CollaborativeField
                            field={fields.description}
                            label={t('roles.form.description')}
                            placeholder={t(
                                'roles.form.description_placeholder',
                            )}
                            reloadOnly={FIELD_PROPS}
                        />
                    )}
                </Card>
            ) : (
                <Card className="grid gap-4 p-5 sm:grid-cols-[1fr_1.5fr]">
                    <div className="grid content-start gap-2">
                        <Label htmlFor="name">{t('roles.form.name')}</Label>
                        <Input
                            id="name"
                            name="name"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            required
                            placeholder={t('roles.form.name_placeholder')}
                        />
                        <InputError message={errors.name} />
                    </div>
                    <div className="grid content-start gap-2">
                        <Label htmlFor="description">
                            {t('roles.form.description')}
                        </Label>
                        <Textarea
                            rows={4}
                            id="description"
                            name="description"
                            value={data.description}
                            onChange={(e) =>
                                setData('description', e.target.value)
                            }
                            placeholder={t(
                                'roles.form.description_placeholder',
                            )}
                        />
                        <InputError message={errors.description} />
                    </div>
                </Card>
            )}

            <section className="space-y-3">
                <div className="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1">
                    <h2 className="text-lg font-semibold">
                        {t('roles.form.permissions')}
                    </h2>
                    <p className="text-sm text-muted-foreground">
                        {isProtected
                            ? t('roles.form.protected_note', { count: total })
                            : t('roles.form.permission_summary', {
                                  count: data.permissions.length,
                                  total,
                              })}
                    </p>
                </div>
                {permissionErrors.map(([field, message]) => (
                    <InputError key={field} message={message} />
                ))}
                <PermissionMatrix
                    groups={permissionGroups}
                    value={data.permissions}
                    onChange={(value) => setData('permissions', value)}
                    locked={isProtected}
                />
            </section>
        </form>
    );
}

/**
 * Someone else changed the role being edited: ask before reloading, so the
 * input is never replaced unasked. Leave when it was deleted.
 */
function LiveRoleUpdates({ role }: { role: RoleData }) {
    const { t } = useLaravelReactI18n();

    useRealtimeResource({
        channel: `role.${role.id}`,
        event: '.RoleChanged',
        only: ['role', 'rolePermissions', 'activity', 'holders'],
        mode: 'ask',
        describe: (_event, actor) =>
            t('roles.realtime.record_updated', { actor }),
        onChange: (event, actor) => {
            if (event.action === ResourceAction.Deleted) {
                toast.warning(t('roles.realtime.record_deleted', { actor }));
                router.visit(index().url);

                return true;
            }
        },
    });

    return null;
}

/** Someone saved a name or description: show it. */
function LiveFieldUpdates({ role }: { role: RoleData }) {
    useCollaborativeFieldSignals({
        channel: `role.${role.id}`,
        only: FIELD_PROPS,
    });

    return null;
}
