import { useLaravelReactI18n } from 'laravel-react-i18n';
import { AlertCircle } from 'lucide-react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import { useClipboard } from '@/hooks/use-clipboard';

/**
 * Shown when a save was refused because someone else saved first. The input
 * stays in the form; the user can copy it before loading the latest version.
 */
export default function ConflictBanner({
    message,
    copyText,
    onReload,
}: {
    /** The server's message: who saved the record, when, and its version. */
    message: string;
    /** The user's own input, as plain text for the clipboard. */
    copyText: string;
    /** Load the latest version; this replaces the input in the form. */
    onReload: () => void;
}) {
    const { t } = useLaravelReactI18n();
    const [, copy] = useClipboard();

    async function copyInput() {
        if (await copy(copyText)) {
            toast.success(t('common.conflict.copied'));

            return;
        }

        toast.error(t('common.conflict.copy_failed'));
    }

    return (
        <div
            role="alert"
            className="flex gap-3.5 rounded-lg border border-destructive/40 bg-destructive/10 p-4"
        >
            <AlertCircle
                aria-hidden
                className="size-5 shrink-0 text-destructive"
            />
            <div className="flex min-w-0 grow flex-col gap-2.5">
                <div className="flex flex-col gap-1">
                    <p className="font-semibold">
                        {t('common.conflict.title')}
                    </p>
                    <p className="text-sm text-muted-foreground">
                        {message} {t('common.conflict.input_kept')}
                    </p>
                </div>
                <div className="flex flex-wrap gap-2">
                    <Button type="button" variant="outline" onClick={copyInput}>
                        {t('common.conflict.copy')}
                    </Button>
                    <Button type="button" onClick={onReload}>
                        {t('common.conflict.reload')}
                    </Button>
                </div>
            </div>
        </div>
    );
}
