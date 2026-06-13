import { Link } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import { Button } from '@/components/ui/button';
import type { LengthAwarePaginator } from '@/types/generated';

/**
 * The `meta` block of any Laravel length-aware paginator. Independent of the
 * row type, so this component works for any paginated index page — pass the
 * `meta` from a `PaginatedDataCollection` prop.
 */
type PaginatorMeta = LengthAwarePaginator<number, unknown>['meta'];

export default function Pagination({ meta }: { meta: PaginatorMeta }) {
    const { t } = useLaravelReactI18n();

    if (meta.total === 0) {
        return null;
    }

    return (
        <div className="flex flex-col items-center justify-between gap-3 sm:flex-row">
            <p className="text-sm text-muted-foreground">
                {t('common.pagination.showing', {
                    from: meta.from ?? 0,
                    to: meta.to ?? 0,
                    total: meta.total,
                })}
            </p>

            <div className="flex items-center gap-2">
                <span className="text-sm text-muted-foreground">
                    {t('common.pagination.page_of', {
                        current: meta.current_page,
                        last: meta.last_page,
                    })}
                </span>

                {meta.prev_page_url ? (
                    <Button variant="outline" size="sm" asChild>
                        <Link
                            href={meta.prev_page_url}
                            preserveScroll
                            preserveState
                        >
                            <ChevronLeft className="size-4" />
                            {t('common.pagination.previous')}
                        </Link>
                    </Button>
                ) : (
                    <Button variant="outline" size="sm" disabled>
                        <ChevronLeft className="size-4" />
                        {t('common.pagination.previous')}
                    </Button>
                )}

                {meta.next_page_url ? (
                    <Button variant="outline" size="sm" asChild>
                        <Link
                            href={meta.next_page_url}
                            preserveScroll
                            preserveState
                        >
                            {t('common.pagination.next')}
                            <ChevronRight className="size-4" />
                        </Link>
                    </Button>
                ) : (
                    <Button variant="outline" size="sm" disabled>
                        {t('common.pagination.next')}
                        <ChevronRight className="size-4" />
                    </Button>
                )}
            </div>
        </div>
    );
}
