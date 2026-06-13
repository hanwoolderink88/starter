import { useEchoPresence } from '@laravel/echo-react';
import { useEffect, useState } from 'react';

export interface RecordViewer {
    id: number;
    name: string;
}

/**
 * Tracks who is currently present on a record's presence channel (including the
 * current user). Pair with a presence channel authorized in routes/channels.php
 * that returns `['id' => ..., 'name' => ...]`.
 */
export function useRecordViewers(channelName: string): RecordViewer[] {
    const [viewers, setViewers] = useState<RecordViewer[]>([]);
    const { channel } = useEchoPresence<RecordViewer>(channelName);

    useEffect(() => {
        const presence = channel();
        if (!presence) return;

        presence
            .here((users: RecordViewer[]) => setViewers(users))
            .joining((user: RecordViewer) =>
                setViewers((current) =>
                    current.some((viewer) => viewer.id === user.id)
                        ? current
                        : [...current, user],
                ),
            )
            .leaving((user: RecordViewer) =>
                setViewers((current) =>
                    current.filter((viewer) => viewer.id !== user.id),
                ),
            );
        // useEchoPresence auto-leaves on unmount; no manual cleanup needed.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [channelName]);

    return viewers;
}
