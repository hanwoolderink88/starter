import { usePage } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import WithTooltip from '@/components/with-tooltip';
import { useInitials } from '@/hooks/use-initials';
import { useRecordViewers } from '@/hooks/use-record-viewers';

/**
 * Shows avatars of other people currently viewing the same record (the current
 * user is excluded). Powered by a presence channel.
 */
export default function RecordViewers({ channel }: { channel: string }) {
    const { t } = useLaravelReactI18n();
    const getInitials = useInitials();
    const { auth } = usePage().props;

    const others = useRecordViewers(channel).filter(
        (viewer) => viewer.id !== auth.user?.id,
    );

    if (others.length === 0) {
        return null;
    }

    return (
        <div className="flex items-center gap-2">
            <span className="text-sm text-muted-foreground">
                {t('users.presence.also_viewing')}
            </span>
            <div className="flex -space-x-2">
                {others.map((viewer) => (
                    <WithTooltip key={viewer.id} label={viewer.name}>
                        <Avatar className="size-7 border-2 border-background">
                            <AvatarFallback className="text-xs">
                                {getInitials(viewer.name)}
                            </AvatarFallback>
                        </Avatar>
                    </WithTooltip>
                ))}
            </div>
        </div>
    );
}
