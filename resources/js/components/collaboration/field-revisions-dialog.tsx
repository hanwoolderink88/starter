import { useHttp } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';
import TextDiff from '@/components/collaboration/text-diff';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Spinner } from '@/components/ui/spinner';
import { cn } from '@/lib/utils';
import {
    index as revisionsRoute,
    restore as restoreRoute,
    show as revisionRoute,
} from '@/routes/collaboration/revisions';
import {
    ActivitySource,
    type CollaborativeFieldData,
    type FieldRevisionData,
    type FieldRevisionDetailData,
} from '@/types/generated';

function messageOf(error: unknown): string | null {
    try {
        const data = (error as { response?: { data: string } }).response?.data;

        return data ? (JSON.parse(data).message ?? null) : null;
    } catch {
        return null;
    }
}

/**
 * The history of a long text field: every closed editing session is a
 * version. Pick one to see its text and how it differs from the current
 * text, and restore it while nobody is editing the field.
 */
export default function FieldRevisionsDialog({
    field,
    label,
    open,
    onOpenChange,
    onRestored,
}: {
    field: CollaborativeFieldData;
    label: string;
    open: boolean;
    onOpenChange: (open: boolean) => void;
    onRestored: () => void;
}) {
    const { t } = useLaravelReactI18n();

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-4xl">
                <DialogHeader>
                    <DialogTitle>
                        {t('collaboration.revisions.title', { field: label })}
                    </DialogTitle>
                    <DialogDescription>
                        {t('collaboration.revisions.description')}
                    </DialogDescription>
                </DialogHeader>

                {open && (
                    <RevisionBrowser field={field} onRestored={onRestored} />
                )}
            </DialogContent>
        </Dialog>
    );
}

function RevisionBrowser({
    field,
    onRestored,
}: {
    field: CollaborativeFieldData;
    onRestored: () => void;
}) {
    const { t } = useLaravelReactI18n();
    const listHttp = useHttp();
    const detailHttp = useHttp();
    const restoreHttp = useHttp();
    const [picked, setPicked] = useState<number | null>(null);
    const [revisions, setRevisions] = useState<FieldRevisionData[] | null>(
        null,
    );
    // Bumped to fetch the list again, after a restore.
    const [listVersion, setListVersion] = useState(0);
    const [detail, setDetail] = useState<FieldRevisionDetailData | null>(null);

    const args = { type: field.type, id: field.id, field: field.field };
    const locked = field.lock !== null;
    const listUrl = revisionsRoute.url(args);
    const selected = picked ?? revisions?.[0]?.number ?? null;
    const detailUrl =
        selected === null
            ? null
            : revisionRoute.url({ ...args, number: selected });

    function loadFailed(): void {
        toast.error(t('collaboration.revisions.load_failed'));
    }

    useEffect(() => {
        listHttp
            .get(listUrl)
            .then((list) => setRevisions(list as FieldRevisionData[]))
            .catch(loadFailed);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [listUrl, listVersion]);

    // The diff is to the current text: fetch again when it changes.
    useEffect(() => {
        if (detailUrl === null) {
            return;
        }

        detailHttp
            .get(detailUrl)
            .then((data) => setDetail(data as FieldRevisionDetailData))
            .catch(loadFailed);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [detailUrl, field.value]);

    const shown =
        detail !== null && detail.revision.number === selected ? detail : null;

    async function restore(number: number): Promise<void> {
        try {
            await restoreHttp.post(restoreRoute.url({ ...args, number }));
            toast.success(t('collaboration.revisions.restored', { number }));
            onRestored();
            setPicked(null);
            setListVersion((version) => version + 1);
        } catch (error) {
            toast.error(
                messageOf(error) ?? t('collaboration.revisions.load_failed'),
            );
        }
    }

    function describe(revision: FieldRevisionData): string {
        const parts = [
            t('collaboration.revisions.by', { name: revision.authorName }),
        ];

        if (revision.impersonatorName) {
            parts.push(
                t('collaboration.revisions.impersonated_by', {
                    name: revision.impersonatorName,
                }),
            );
        }

        if (revision.source === ActivitySource.Mcp) {
            parts.push(t('collaboration.revisions.via_claude'));
        }

        if (revision.restoredFromNumber !== null) {
            parts.push(
                t('collaboration.revisions.restored_from', {
                    number: revision.restoredFromNumber,
                }),
            );
        }

        return parts.join(' · ');
    }

    return (
        <>
            {revisions === null ? (
                <div className="flex justify-center p-6">
                    <Spinner />
                </div>
            ) : revisions.length === 0 ? (
                <p className="text-sm text-muted-foreground">
                    {t('collaboration.revisions.empty')}
                </p>
            ) : (
                <div className="grid gap-4 md:grid-cols-[16rem_1fr]">
                    <ol className="space-y-1">
                        {revisions.map((revision) => (
                            <li key={revision.number}>
                                <button
                                    type="button"
                                    onClick={() => setPicked(revision.number)}
                                    aria-current={selected === revision.number}
                                    className={cn(
                                        'w-full rounded-md px-3 py-2 text-left text-sm hover:bg-muted',
                                        selected === revision.number &&
                                            'bg-muted',
                                    )}
                                >
                                    <span className="block font-medium">
                                        {t('collaboration.revisions.number', {
                                            number: revision.number,
                                        })}
                                    </span>
                                    <span className="block text-xs text-muted-foreground">
                                        <time dateTime={revision.createdAt}>
                                            {revision.createdAtDisplay}
                                        </time>
                                        {' · '}
                                        {describe(revision)}
                                    </span>
                                </button>
                            </li>
                        ))}
                    </ol>

                    <div className="min-w-0 space-y-4">
                        {shown === null ? (
                            <div className="flex justify-center p-6">
                                <Spinner />
                            </div>
                        ) : (
                            <>
                                <section className="space-y-2">
                                    <div className="flex flex-wrap items-baseline justify-between gap-2">
                                        <h3 className="text-sm font-semibold">
                                            {t(
                                                'collaboration.revisions.changes',
                                            )}
                                        </h3>
                                        {shown.diffToCurrent.hunks.length >
                                            0 && (
                                            <p className="text-xs text-muted-foreground tabular-nums">
                                                <span className="text-green-700 dark:text-green-400">
                                                    {t(
                                                        'collaboration.revisions.additions',
                                                        {
                                                            count: shown
                                                                .diffToCurrent
                                                                .additions,
                                                        },
                                                    )}
                                                </span>{' '}
                                                <span className="text-red-700 dark:text-red-400">
                                                    {t(
                                                        'collaboration.revisions.deletions',
                                                        {
                                                            count: shown
                                                                .diffToCurrent
                                                                .deletions,
                                                        },
                                                    )}
                                                </span>
                                            </p>
                                        )}
                                    </div>
                                    {shown.diffToCurrent.hunks.length === 0 ? (
                                        <p className="text-sm text-muted-foreground">
                                            {t(
                                                'collaboration.revisions.no_changes',
                                            )}
                                        </p>
                                    ) : (
                                        <TextDiff diff={shown.diffToCurrent} />
                                    )}
                                </section>

                                <section className="space-y-2">
                                    <h3 className="text-sm font-semibold">
                                        {t('collaboration.revisions.content')}
                                    </h3>
                                    <p className="rounded-md border p-3 text-sm whitespace-pre-wrap">
                                        {shown.content}
                                    </p>
                                </section>

                                {field.canEdit &&
                                    shown.diffToCurrent.hunks.length > 0 && (
                                        <div className="space-y-2">
                                            <Button
                                                type="button"
                                                disabled={
                                                    locked ||
                                                    restoreHttp.processing
                                                }
                                                onClick={() =>
                                                    void restore(
                                                        shown.revision.number,
                                                    )
                                                }
                                            >
                                                {t(
                                                    'collaboration.revisions.restore',
                                                )}
                                            </Button>
                                            {locked && (
                                                <p className="text-sm text-muted-foreground">
                                                    {t(
                                                        'collaboration.revisions.restore_locked',
                                                    )}
                                                </p>
                                            )}
                                        </div>
                                    )}
                            </>
                        )}
                    </div>
                </div>
            )}
        </>
    );
}
