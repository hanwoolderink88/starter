import { router } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { Check, History, Pencil, WifiOff } from 'lucide-react';
import { useState } from 'react';
import FieldRevisionsDialog from '@/components/collaboration/field-revisions-dialog';
import ConflictBanner from '@/components/conflict-banner';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { useCollaborativeField } from '@/hooks/use-collaborative-field';
import { cn } from '@/lib/utils';
import {
    ActivitySource,
    type CollaborativeFieldData,
    type FieldLockData,
} from '@/types/generated';

function useLockLabel(lock: FieldLockData | null): string | null {
    const { t } = useLaravelReactI18n();

    if (lock === null || lock.isThisTab) {
        return null;
    }

    if (lock.isMine) {
        return t('collaboration.field.other_tab');
    }

    if (lock.impersonatorName) {
        return t('collaboration.field.editing_by_impersonated', {
            name: lock.userName,
            impersonator: lock.impersonatorName,
        });
    }

    if (lock.source === ActivitySource.Mcp) {
        return t('collaboration.field.editing_via_claude', {
            name: lock.userName,
        });
    }

    return t('collaboration.field.editing_by', { name: lock.userName });
}

/**
 * A field that saves as you type, one editor at a time. Focusing it claims
 * it; while someone else edits it, it shows who and follows their saved
 * text. A long text (`multiline`) keeps a history of its editing sessions.
 */
export default function CollaborativeField({
    field,
    label,
    placeholder,
    reloadOnly,
}: {
    field: CollaborativeFieldData;
    label: string;
    placeholder?: string;
    /** The props that show this record's fields. */
    reloadOnly: string[];
}) {
    const { t } = useLaravelReactI18n();
    const collaboration = useCollaborativeField({ field, reloadOnly });
    const [historyOpen, setHistoryOpen] = useState(false);
    const lockLabel = useLockLabel(field.lock);

    const id = `field-${field.type}-${field.id}-${field.field}`;
    const { phase, saveStatus } = collaboration;
    const disabled = !field.canEdit || collaboration.lockedByOther;

    const inputProps = {
        id,
        name: field.field,
        value: collaboration.value,
        placeholder,
        disabled,
        readOnly: collaboration.readOnly,
        'aria-invalid': collaboration.error !== null || undefined,
        'aria-describedby': `${id}-status`,
        onFocus: () => void collaboration.claim(),
        onBlur: () => void collaboration.done(),
        onKeyDown: (event: React.KeyboardEvent) => {
            if (event.key === 'Escape') {
                (event.currentTarget as HTMLElement).blur();
            }
        },
        onChange: (
            event: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>,
        ) => collaboration.change(event.target.value),
        className: cn(
            collaboration.lockedByOther &&
                'border-amber-500/60 bg-amber-50/50 dark:bg-amber-950/20',
        ),
    };

    return (
        <div className="grid content-start gap-2">
            <div className="flex min-h-5 flex-wrap items-center justify-between gap-2">
                <Label htmlFor={id}>{label}</Label>
                <div className="flex items-center gap-2">
                    <span
                        id={`${id}-status`}
                        aria-live="polite"
                        className="flex items-center gap-1 text-xs text-muted-foreground"
                    >
                        {lockLabel !== null && (
                            <span className="flex items-center gap-1 text-amber-700 dark:text-amber-400">
                                <Pencil aria-hidden className="size-3" />
                                {lockLabel}
                            </span>
                        )}
                        {lockLabel === null && saveStatus === 'saving' && (
                            <>
                                <Spinner className="size-3" />
                                {t('collaboration.field.saving')}
                            </>
                        )}
                        {lockLabel === null && saveStatus === 'saved' && (
                            <>
                                <Check aria-hidden className="size-3" />
                                {t('collaboration.field.saved')}
                            </>
                        )}
                        {lockLabel === null && saveStatus === 'unsaved' && (
                            <span className="text-destructive">
                                {t('collaboration.field.unsaved')}
                            </span>
                        )}
                    </span>
                    {field.multiline && field.revisionCount > 0 && (
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            className="h-5 gap-1 px-1.5 text-xs"
                            onClick={() => setHistoryOpen(true)}
                        >
                            <History aria-hidden className="size-3" />
                            {t('collaboration.field.history')}
                        </Button>
                    )}
                </div>
            </div>

            {field.multiline ? (
                <Textarea rows={4} {...inputProps} />
            ) : (
                <Input {...inputProps} />
            )}

            <InputError message={collaboration.error ?? undefined} />

            {phase === 'offline' && (
                <p
                    role="status"
                    className="flex items-center gap-2 text-sm text-muted-foreground"
                >
                    <WifiOff aria-hidden className="size-4 shrink-0" />
                    {t('collaboration.field.offline')}
                </p>
            )}

            {phase === 'expired' && (
                <div
                    role="status"
                    className="flex flex-wrap items-center gap-2 text-sm text-muted-foreground"
                >
                    {t('collaboration.field.expired')}
                    {!disabled && (
                        <Button
                            type="button"
                            variant="link"
                            className="h-auto p-0"
                            onClick={() => document.getElementById(id)?.focus()}
                        >
                            {t('collaboration.field.continue')}
                        </Button>
                    )}
                </div>
            )}

            {phase === 'conflict' && collaboration.conflictMessage && (
                <ConflictBanner
                    message={collaboration.conflictMessage}
                    copyText={collaboration.value}
                    onReload={collaboration.reloadAfterConflict}
                />
            )}

            {field.multiline && (
                <FieldRevisionsDialog
                    field={field}
                    label={label}
                    open={historyOpen}
                    onOpenChange={setHistoryOpen}
                    onRestored={() => router.reload({ only: reloadOnly })}
                />
            )}
        </div>
    );
}
