import { cn } from '@/lib/utils';
import { DiffLineType, type TextDiffData } from '@/types/generated';

const lineStyles: Record<DiffLineType, string> = {
    [DiffLineType.Context]: 'text-muted-foreground',
    [DiffLineType.Removed]:
        'bg-red-50 text-red-900 dark:bg-red-950/50 dark:text-red-200',
    [DiffLineType.Added]:
        'bg-green-50 text-green-900 dark:bg-green-950/50 dark:text-green-200',
};

const markers: Record<DiffLineType, string> = {
    [DiffLineType.Context]: ' ',
    [DiffLineType.Removed]: '−',
    [DiffLineType.Added]: '+',
};

/** A line diff as hunks, the way the server computed it. */
export default function TextDiff({ diff }: { diff: TextDiffData }) {
    return (
        <div className="overflow-x-auto rounded-md border font-mono text-xs">
            {diff.hunks.map((hunk, index) => (
                <div key={index} className={cn(index > 0 && 'border-t')}>
                    <div className="bg-muted px-3 py-1 text-muted-foreground">
                        @@ −{hunk.oldStart},{hunk.oldLines} +{hunk.newStart},
                        {hunk.newLines} @@
                    </div>
                    {hunk.lines.map((line, lineIndex) => (
                        <div
                            key={lineIndex}
                            className={cn(
                                'flex gap-3 px-3 whitespace-pre-wrap',
                                lineStyles[line.type],
                            )}
                        >
                            <span
                                aria-hidden
                                className="w-3 shrink-0 select-none"
                            >
                                {markers[line.type]}
                            </span>
                            <span className="min-w-0 break-words">
                                {line.content || ' '}
                            </span>
                        </div>
                    ))}
                </div>
            ))}
        </div>
    );
}
