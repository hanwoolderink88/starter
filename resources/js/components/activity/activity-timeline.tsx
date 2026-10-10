import { Link } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { ChevronDown } from 'lucide-react';
import ActivityDetails from '@/components/activity/activity-details';
import { Card, CardContent } from '@/components/ui/card';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import { actorLabel } from '@/lib/actor-label';
import { index as activityIndex } from '@/routes/activity';
import type { ActivityData } from '@/types/generated';

/**
 * The newest log entries about one record, newest first, each expandable to
 * its details. Links to the full log filtered on the record.
 */
export default function ActivityTimeline({
    activity,
    subjectType,
    subjectId,
}: {
    activity: ActivityData[];
    subjectType: string;
    subjectId: number;
}) {
    const { t } = useLaravelReactI18n();

    return (
        <section className="space-y-3" aria-labelledby="activity-timeline">
            <div className="flex items-end justify-between gap-4">
                <div>
                    <h3 id="activity-timeline" className="font-medium">
                        {t('activity.timeline.title')}
                    </h3>
                    <p className="text-sm text-muted-foreground">
                        {t('activity.timeline.description')}
                    </p>
                </div>
                <div className="flex shrink-0 flex-col items-end gap-1 text-sm">
                    <Link
                        href={activityIndex({
                            query: {
                                subject_type: subjectType,
                                subject_id: subjectId,
                            },
                        })}
                        className="underline-offset-4 hover:underline"
                    >
                        {t('activity.timeline.view_all')}
                    </Link>
                    {subjectType === 'user' && (
                        <Link
                            href={activityIndex({
                                query: { causer_id: subjectId },
                            })}
                            className="underline-offset-4 hover:underline"
                        >
                            {t('activity.timeline.performed_by_user')}
                        </Link>
                    )}
                </div>
            </div>

            <Card className="p-0">
                <CardContent className="p-0">
                    {activity.length === 0 ? (
                        <p className="p-4 text-sm text-muted-foreground">
                            {t('activity.timeline.empty')}
                        </p>
                    ) : (
                        <ol className="divide-y">
                            {activity.map((entry) => (
                                <li key={entry.id}>
                                    <Collapsible>
                                        <CollapsibleTrigger className="group flex w-full items-start justify-between gap-4 p-4 text-left text-sm hover:bg-muted/50 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none">
                                            <span className="space-y-0.5">
                                                <span className="block font-medium">
                                                    {t(
                                                        `activity.events.${entry.event}`,
                                                    )}
                                                </span>
                                                <span className="block text-muted-foreground">
                                                    {actorLabel(t, {
                                                        name: entry.causerName,
                                                        impersonatorName:
                                                            entry.impersonatorName,
                                                        source: entry.source,
                                                    })}
                                                </span>
                                            </span>
                                            <span className="flex shrink-0 items-center gap-2 text-muted-foreground tabular-nums">
                                                <time
                                                    dateTime={entry.createdAt}
                                                >
                                                    {entry.createdAtDisplay}
                                                </time>
                                                <ChevronDown className="size-4 transition-transform group-data-[state=open]:rotate-180" />
                                            </span>
                                        </CollapsibleTrigger>
                                        <CollapsibleContent className="px-4 pb-4">
                                            <ActivityDetails activity={entry} />
                                        </CollapsibleContent>
                                    </Collapsible>
                                </li>
                            ))}
                        </ol>
                    )}
                </CardContent>
            </Card>
        </section>
    );
}
