import { useLaravelReactI18n } from 'laravel-react-i18n';
import type { ActivityData } from '@/types/generated';

type FieldChange = { old: unknown; new: unknown };

function isFieldChange(value: unknown): value is FieldChange {
    return (
        typeof value === 'object' &&
        value !== null &&
        'old' in value &&
        'new' in value
    );
}

function formatValue(value: unknown): string {
    if (value === null || value === undefined || value === '') {
        return '—';
    }

    if (typeof value === 'object') {
        return JSON.stringify(value);
    }

    return String(value);
}

/**
 * Everything an entry records beyond its headline: per changed field the old
 * and new value, the state of a deleted record, event details such as the
 * email address of a failed login, and where the request came from.
 */
export default function ActivityDetails({
    activity,
}: {
    activity: ActivityData;
}) {
    const { t } = useLaravelReactI18n();
    const entries = Object.entries(activity.changes);
    const fieldChanges = entries.filter(([, value]) => isFieldChange(value));
    const details = entries.filter(([, value]) => !isFieldChange(value));
    const snapshot = Object.entries(activity.snapshot ?? {});

    return (
        <div className="space-y-4 text-sm">
            {fieldChanges.length > 0 && (
                <table className="w-full table-fixed">
                    <thead className="text-left text-muted-foreground">
                        <tr>
                            <th className="pb-1 font-normal">
                                {t('activity.entry.field')}
                            </th>
                            <th className="pb-1 font-normal">
                                {t('activity.entry.old')}
                            </th>
                            <th className="pb-1 font-normal">
                                {t('activity.entry.new')}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        {fieldChanges.map(([field, value]) => {
                            const change = value as FieldChange;

                            return (
                                <tr key={field} className="align-top">
                                    <td className="py-0.5 pr-2 font-medium">
                                        {field}
                                    </td>
                                    <td className="py-0.5 pr-2 break-words text-muted-foreground line-through">
                                        {formatValue(change.old)}
                                    </td>
                                    <td className="py-0.5 break-words">
                                        {formatValue(change.new)}
                                    </td>
                                </tr>
                            );
                        })}
                    </tbody>
                </table>
            )}

            {snapshot.length > 0 && (
                <div className="space-y-1">
                    <p className="text-muted-foreground">
                        {t('activity.entry.snapshot')}
                    </p>
                    <DetailList items={snapshot} />
                </div>
            )}

            <DetailList
                items={[
                    ...details,
                    [t('activity.entry.ip_address'), activity.ipAddress],
                    [t('activity.entry.user_agent'), activity.userAgent],
                ]}
            />
        </div>
    );
}

function DetailList({ items }: { items: [string, unknown][] }) {
    return (
        <dl className="grid grid-cols-[minmax(0,10rem)_1fr] gap-x-4 gap-y-0.5">
            {items.map(([label, value]) => (
                <div key={label} className="contents">
                    <dt className="text-muted-foreground">{label}</dt>
                    <dd className="break-words">{formatValue(value)}</dd>
                </div>
            ))}
        </dl>
    );
}
