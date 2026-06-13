import { useLaravelReactI18n } from 'laravel-react-i18n';
import { Filter, Search } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
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
import type { UserFiltersData } from '@/types/generated';

const ALL = 'all';

export type UserFilterChange = Partial<{
    search: string | null;
    role: string | null;
    status: string | null;
}>;

type FilterControlsProps = {
    filters: UserFiltersData;
    roleOptions: Record<string, string>;
    statusOptions: Record<string, string>;
    onChange: (change: UserFilterChange) => void;
    /** `stacked` renders labelled, full-width controls for the mobile sheet. */
    stacked?: boolean;
};

/** The role + status selects, shared between the desktop row and mobile sheet. */
function FilterControls({
    filters,
    roleOptions,
    statusOptions,
    onChange,
    stacked = false,
}: FilterControlsProps) {
    const { t } = useLaravelReactI18n();
    const triggerClass = stacked ? 'w-full' : 'sm:w-44';

    const role = (
        <Select
            value={filters.role ?? ALL}
            onValueChange={(value) =>
                onChange({ role: value === ALL ? null : value })
            }
        >
            <SelectTrigger
                className={triggerClass}
                aria-label={t('users.filters.role')}
            >
                <SelectValue placeholder={t('users.filters.role')} />
            </SelectTrigger>
            <SelectContent>
                <SelectItem value={ALL}>
                    {t('users.filters.all_roles')}
                </SelectItem>
                {Object.entries(roleOptions).map(([value, label]) => (
                    <SelectItem key={value} value={value}>
                        {label}
                    </SelectItem>
                ))}
            </SelectContent>
        </Select>
    );

    const status = (
        <Select
            value={filters.status ?? ALL}
            onValueChange={(value) =>
                onChange({ status: value === ALL ? null : value })
            }
        >
            <SelectTrigger
                className={triggerClass}
                aria-label={t('users.filters.status')}
            >
                <SelectValue placeholder={t('users.filters.status')} />
            </SelectTrigger>
            <SelectContent>
                <SelectItem value={ALL}>
                    {t('users.filters.all_statuses')}
                </SelectItem>
                {Object.entries(statusOptions).map(([value, label]) => (
                    <SelectItem key={value} value={value}>
                        {label}
                    </SelectItem>
                ))}
            </SelectContent>
        </Select>
    );

    if (!stacked) {
        return (
            <>
                {role}
                {status}
            </>
        );
    }

    return (
        <div className="grid gap-4">
            <div className="grid gap-2">
                <Label>{t('users.filters.role')}</Label>
                {role}
            </div>
            <div className="grid gap-2">
                <Label>{t('users.filters.status')}</Label>
                {status}
            </div>
        </div>
    );
}

export default function UsersFilters({
    filters,
    roleOptions,
    statusOptions,
    onChange,
}: {
    filters: UserFiltersData;
    roleOptions: Record<string, string>;
    statusOptions: Record<string, string>;
    onChange: (change: UserFilterChange) => void;
}) {
    const { t } = useLaravelReactI18n();
    const [search, setSearch] = useState(filters.search ?? '');
    const mounted = useRef(false);

    // Number of active secondary filters — shown as a badge on the mobile button.
    const activeCount = [filters.role, filters.status].filter(Boolean).length;

    // Debounce the free-text search; selects apply immediately on change.
    useEffect(() => {
        if (!mounted.current) {
            mounted.current = true;
            return;
        }

        const timeout = setTimeout(
            () => onChange({ search: search.trim() || null }),
            300,
        );

        return () => clearTimeout(timeout);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [search]);

    return (
        <div className="flex items-center gap-2 sm:gap-3">
            <div className="relative flex-1">
                <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                <Input
                    type="search"
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
                    placeholder={t('users.filters.search_placeholder')}
                    aria-label={t('users.filters.search_placeholder')}
                    className="pl-9"
                />
            </div>

            {/* Desktop: inline selects. */}
            <div className="hidden gap-3 sm:flex">
                <FilterControls
                    filters={filters}
                    roleOptions={roleOptions}
                    statusOptions={statusOptions}
                    onChange={onChange}
                />
            </div>

            {/* Mobile: filters collapse into a bottom sheet. */}
            <Sheet>
                <SheetTrigger asChild>
                    <Button variant="outline" className="shrink-0 sm:hidden">
                        <Filter className="size-4" />
                        {t('users.filters.button')}
                        {activeCount > 0 && (
                            <Badge variant="secondary" className="ml-1">
                                {activeCount}
                            </Badge>
                        )}
                    </Button>
                </SheetTrigger>
                <SheetContent side="bottom">
                    <SheetHeader>
                        <SheetTitle>{t('users.filters.heading')}</SheetTitle>
                    </SheetHeader>
                    <div className="px-4">
                        <FilterControls
                            filters={filters}
                            roleOptions={roleOptions}
                            statusOptions={statusOptions}
                            onChange={onChange}
                            stacked
                        />
                    </div>
                    <SheetFooter className="flex-row justify-end gap-2">
                        <Button
                            variant="ghost"
                            disabled={activeCount === 0}
                            onClick={() =>
                                onChange({ role: null, status: null })
                            }
                        >
                            {t('users.filters.clear')}
                        </Button>
                        <SheetClose asChild>
                            <Button>{t('users.filters.done')}</Button>
                        </SheetClose>
                    </SheetFooter>
                </SheetContent>
            </Sheet>
        </div>
    );
}
