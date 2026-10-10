import { useLaravelReactI18n } from 'laravel-react-i18n';
import { Filter } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Sheet,
    SheetClose,
    SheetContent,
    SheetFooter,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import type { ActivityFilterChange } from '@/hooks/use-activity-filters';
import {
    ActivityEvent,
    ActivitySource,
    type ActivityCauserData,
    type ActivityFiltersData,
} from '@/types/generated';

const ALL = 'all';

type FilterProps = {
    filters: ActivityFiltersData;
    causerOptions: ActivityCauserData[];
    subjectTypeOptions: string[];
    onChange: (change: ActivityFilterChange) => void;
};

/** The filter controls, shared between the desktop grid and mobile sheet. */
function FilterControls({
    filters,
    causerOptions,
    subjectTypeOptions,
    onChange,
}: FilterProps) {
    const { t } = useLaravelReactI18n();

    function select(
        id: string,
        label: string,
        value: string | null,
        allLabel: string,
        options: [string, string][],
        apply: (value: string | null) => void,
    ) {
        return (
            <div className="grid gap-1.5">
                <Label htmlFor={id}>{label}</Label>
                <Select
                    value={value ?? ALL}
                    onValueChange={(next) => apply(next === ALL ? null : next)}
                >
                    <SelectTrigger id={id} className="w-full">
                        <SelectValue placeholder={label} />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value={ALL}>{allLabel}</SelectItem>
                        {options.map(([optionValue, optionLabel]) => (
                            <SelectItem key={optionValue} value={optionValue}>
                                {optionLabel}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </div>
        );
    }

    return (
        <>
            {select(
                'activity-event',
                t('activity.filters.event'),
                filters.event,
                t('activity.filters.all_events'),
                Object.values(ActivityEvent).map((event) => [
                    event,
                    t(`activity.events.${event}`),
                ]),
                (event) => onChange({ event }),
            )}
            {select(
                'activity-causer',
                t('activity.filters.causer'),
                filters.causerId === null ? null : String(filters.causerId),
                t('activity.filters.all_causers'),
                causerOptions.map((causer) => [String(causer.id), causer.name]),
                (causer) =>
                    onChange({
                        causer_id: causer === null ? null : Number(causer),
                    }),
            )}
            {select(
                'activity-subject-type',
                t('activity.filters.subject_type'),
                filters.subjectType,
                t('activity.filters.all_subject_types'),
                subjectTypeOptions.map((type) => [
                    type,
                    t(`activity.subjects.${type}`),
                ]),
                (type) => onChange({ subject_type: type, subject_id: null }),
            )}
            {select(
                'activity-source',
                t('activity.filters.source'),
                filters.source,
                t('activity.filters.all_sources'),
                Object.values(ActivitySource).map((source) => [
                    source,
                    t(`activity.sources.${source}`),
                ]),
                (source) => onChange({ source }),
            )}
            <div className="grid gap-1.5">
                <Label htmlFor="activity-from">
                    {t('activity.filters.from')}
                </Label>
                <Input
                    id="activity-from"
                    type="date"
                    value={filters.from ?? ''}
                    max={filters.to ?? undefined}
                    onChange={(e) => onChange({ from: e.target.value || null })}
                />
            </div>
            <div className="grid gap-1.5">
                <Label htmlFor="activity-to">{t('activity.filters.to')}</Label>
                <Input
                    id="activity-to"
                    type="date"
                    value={filters.to ?? ''}
                    min={filters.from ?? undefined}
                    onChange={(e) => onChange({ to: e.target.value || null })}
                />
            </div>
        </>
    );
}

export default function ActivityFilters({
    onClear,
    ...props
}: FilterProps & { onClear: () => void }) {
    const { t } = useLaravelReactI18n();
    const { filters } = props;

    const activeCount = [
        filters.event,
        filters.source,
        filters.causerId,
        filters.subjectType,
        filters.from,
        filters.to,
    ].filter((value) => value !== null).length;

    return (
        <>
            {/* Desktop: all filters in one grid. */}
            <div className="hidden gap-3 sm:grid sm:grid-cols-3 lg:grid-cols-6">
                <FilterControls {...props} />
            </div>

            {/* Mobile: filters collapse into a bottom sheet. */}
            <Sheet>
                <SheetTrigger asChild>
                    <Button variant="outline" className="sm:hidden">
                        <Filter className="size-4" />
                        {t('activity.filters.button')}
                        {activeCount > 0 && (
                            <Badge variant="secondary" className="ml-1">
                                {activeCount}
                            </Badge>
                        )}
                    </Button>
                </SheetTrigger>
                <SheetContent side="bottom">
                    <SheetHeader>
                        <SheetTitle>{t('activity.filters.heading')}</SheetTitle>
                    </SheetHeader>
                    <div className="grid gap-4 px-4">
                        <FilterControls {...props} />
                    </div>
                    <SheetFooter className="flex-row justify-end gap-2">
                        <Button
                            variant="ghost"
                            disabled={activeCount === 0}
                            onClick={onClear}
                        >
                            {t('activity.filters.clear')}
                        </Button>
                        <SheetClose asChild>
                            <Button>{t('activity.filters.done')}</Button>
                        </SheetClose>
                    </SheetFooter>
                </SheetContent>
            </Sheet>

            {activeCount > 0 && (
                <Button
                    variant="ghost"
                    size="sm"
                    className="hidden sm:inline-flex"
                    onClick={onClear}
                >
                    {t('activity.filters.clear')}
                </Button>
            )}
        </>
    );
}
