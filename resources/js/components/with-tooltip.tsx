import type { ReactNode } from 'react';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';

/**
 * Wraps any interactive element with a tooltip. Pair with an `aria-label` on the
 * trigger so the control stays accessible without relying on hover/pointer.
 */
export default function WithTooltip({
    label,
    side = 'bottom',
    children,
}: {
    label: string;
    side?: 'top' | 'right' | 'bottom' | 'left';
    children: ReactNode;
}) {
    return (
        <Tooltip>
            <TooltipTrigger asChild>{children}</TooltipTrigger>
            <TooltipContent side={side}>{label}</TooltipContent>
        </Tooltip>
    );
}
