import { Head } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import ActivityFilters from '@/components/activity/activity-filters';
import ActivityLogTable from '@/components/activity/activity-log-table';
import Heading from '@/components/heading';
import Pagination from '@/components/pagination';
import { useActivityFilters } from '@/hooks/use-activity-filters';
import AppLayout from '@/layouts/app-layout';
import { index } from '@/routes/activity';
import type { BreadcrumbItem, PageProps } from '@/types';
import type { ActivityPageData } from '@/types/generated';

export default function ActivityIndex({
    entries,
    filters,
    causerOptions,
    subjectTypeOptions,
}: PageProps<ActivityPageData>) {
    const { t } = useLaravelReactI18n();
    const { applyFilters, clearFilters } = useActivityFilters(filters);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: t('activity.breadcrumb.index'), href: index().url },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={t('activity.title')} />

            <div className="mx-auto w-full max-w-6xl space-y-6 p-4">
                <Heading
                    title={t('activity.title')}
                    description={t('activity.description')}
                />

                <ActivityFilters
                    filters={filters}
                    causerOptions={causerOptions}
                    subjectTypeOptions={subjectTypeOptions}
                    onChange={applyFilters}
                    onClear={clearFilters}
                />

                <ActivityLogTable entries={entries.data} />

                <Pagination meta={entries.meta} />
            </div>
        </AppLayout>
    );
}
