import { Link } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { ChevronDown } from 'lucide-react';
import { Fragment, useState } from 'react';
import ActivityDetails from '@/components/activity/activity-details';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { actorLabel } from '@/lib/actor-label';
import { cn } from '@/lib/utils';
import { show as showUser } from '@/routes/users';
import type { ActivityLogEntryData } from '@/types/generated';

const COLUMNS = 6;

function SubjectName({ entry }: { entry: ActivityLogEntryData }) {
    const { t } = useLaravelReactI18n();
    const { activity, subjectName, subjectExists } = entry;

    if (subjectName === null) {
        return <span className="text-muted-foreground">—</span>;
    }

    if (!subjectExists) {
        return (
            <span className="text-muted-foreground">
                {t('activity.entry.deleted_subject', { name: subjectName })}
            </span>
        );
    }

    if (activity.subjectType === 'user' && activity.subjectId !== null) {
        return (
            <Link
                href={showUser(activity.subjectId)}
                className="underline-offset-4 hover:underline"
            >
                {subjectName}
            </Link>
        );
    }

    return <span>{subjectName}</span>;
}

/**
 * The activity log: one row per entry, newest first, each expandable to its
 * details.
 */
export default function ActivityLogTable({
    entries,
}: {
    entries: ActivityLogEntryData[];
}) {
    const { t } = useLaravelReactI18n();
    const [openId, setOpenId] = useState<number | null>(null);

    return (
        <Card className="p-0">
            <CardContent className="p-0">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>{t('activity.columns.when')}</TableHead>
                            <TableHead>{t('activity.columns.event')}</TableHead>
                            <TableHead className="hidden md:table-cell">
                                {t('activity.columns.subject')}
                            </TableHead>
                            <TableHead className="hidden sm:table-cell">
                                {t('activity.columns.causer')}
                            </TableHead>
                            <TableHead className="hidden lg:table-cell">
                                {t('activity.columns.origin')}
                            </TableHead>
                            <TableHead className="w-10">
                                <span className="sr-only">
                                    {t('activity.entry.details')}
                                </span>
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {entries.length === 0 && (
                            <TableRow>
                                <TableCell
                                    colSpan={COLUMNS}
                                    className="py-8 text-center text-muted-foreground"
                                >
                                    {t('activity.empty')}
                                </TableCell>
                            </TableRow>
                        )}
                        {entries.map((entry) => {
                            const { activity } = entry;
                            const open = openId === activity.id;

                            return (
                                <Fragment key={activity.id}>
                                    <TableRow
                                        className={cn(open && 'border-b-0')}
                                    >
                                        <TableCell className="whitespace-normal tabular-nums sm:whitespace-nowrap">
                                            <time dateTime={activity.createdAt}>
                                                {activity.createdAtDisplay}
                                            </time>
                                        </TableCell>
                                        <TableCell className="font-medium whitespace-normal sm:whitespace-nowrap">
                                            {t(
                                                `activity.events.${activity.event}`,
                                            )}
                                        </TableCell>
                                        <TableCell className="hidden md:table-cell">
                                            <SubjectName entry={entry} />
                                        </TableCell>
                                        <TableCell className="hidden sm:table-cell">
                                            {actorLabel(t, {
                                                name: activity.causerName,
                                                impersonatorName:
                                                    activity.impersonatorName,
                                                source: activity.source,
                                            })}
                                        </TableCell>
                                        <TableCell className="hidden text-muted-foreground tabular-nums lg:table-cell">
                                            {activity.ipAddress ?? '—'}
                                        </TableCell>
                                        <TableCell>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                className="size-8"
                                                aria-expanded={open}
                                                aria-label={t(
                                                    'activity.entry.details',
                                                )}
                                                onClick={() =>
                                                    setOpenId(
                                                        open
                                                            ? null
                                                            : activity.id,
                                                    )
                                                }
                                            >
                                                <ChevronDown
                                                    className={cn(
                                                        'size-4 transition-transform',
                                                        open && 'rotate-180',
                                                    )}
                                                />
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                    {open && (
                                        <TableRow className="hover:bg-transparent">
                                            <TableCell
                                                colSpan={COLUMNS}
                                                className="bg-muted/30 whitespace-normal"
                                            >
                                                <ActivityDetails
                                                    activity={activity}
                                                />
                                            </TableCell>
                                        </TableRow>
                                    )}
                                </Fragment>
                            );
                        })}
                    </TableBody>
                </Table>
            </CardContent>
        </Card>
    );
}
