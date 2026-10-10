import { router, useHttp } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { useEffect, useEffectEvent, useRef, useState } from 'react';
import { toast } from 'sonner';
import { useConnectionRecovery } from '@/hooks/use-connection-recovery';
import { getClientId } from '@/lib/client-id';
import {
    destroy as releaseRoute,
    store as claimRoute,
    update as heartbeatRoute,
} from '@/routes/collaboration/lock';
import { update as saveRoute } from '@/routes/collaboration/value';
import type { CollaborativeFieldData } from '@/types/generated';

/** Autosave this long after the last keystroke. */
export const AUTOSAVE_DEBOUNCE_MS = 1000;

/** `config('collaboration.heartbeat_seconds')`. */
const HEARTBEAT_SECONDS = 15;

/** `config('collaboration.idle_seconds')`. */
const IDLE_SECONDS = 120;

/**
 * Where this tab is with the field:
 * - `viewing`: not editing (the field may be free or held by someone else);
 * - `claiming`: the claim is on its way; typing is kept and saved after it;
 * - `editing`: this tab holds the field and saves as it is typed;
 * - `offline`: the connection dropped while editing; the text is kept and
 *   saved once it is back;
 * - `conflict`: after the connection came back the field had changed, or
 *   someone else held it; the local text stays for copying;
 * - `expired`: the session closed after a while without typing.
 */
export type CollaborativeFieldPhase =
    'viewing' | 'claiming' | 'editing' | 'offline' | 'conflict' | 'expired';

export type CollaborativeFieldSaveStatus =
    'idle' | 'pending' | 'saving' | 'saved' | 'unsaved';

type HttpFailure = { response?: { status: number; data: string } };

function statusOf(error: unknown): number | null {
    return (error as HttpFailure).response?.status ?? null;
}

function bodyOf(error: unknown): {
    message?: string;
    errors?: Record<string, string[]>;
} {
    try {
        const data = (error as HttpFailure).response?.data;

        return data ? JSON.parse(data) : {};
    } catch {
        return {};
    }
}

function xsrfToken(): string {
    const match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/);

    return match ? decodeURIComponent(match[1]) : '';
}

/**
 * A request that survives leaving the page: a page that unloads cannot wait
 * for `useHttp`. Best effort; a lost release expires on its own.
 */
function keepaliveRequest(
    url: string,
    method: 'PUT' | 'DELETE',
    body?: Record<string, unknown>,
): Promise<unknown> {
    return fetch(url, {
        method,
        keepalive: true,
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'X-XSRF-TOKEN': xsrfToken(),
            'X-Client-Id': getClientId(),
            ...(body ? { 'Content-Type': 'application/json' } : {}),
        },
        body: body ? JSON.stringify(body) : undefined,
    }).catch(() => null);
}

/**
 * Editing one field together: focusing it claims it, typing saves it a
 * second after the last keystroke (one save at a time), and leaving it,
 * pressing Escape, navigating away or closing the tab releases it. A
 * heartbeat keeps it held while the field is open; a session without typing
 * ends on its own. While this tab holds the field it never takes its value
 * from the props; otherwise it follows them.
 */
export function useCollaborativeField({
    field,
    reloadOnly,
}: {
    field: CollaborativeFieldData;
    /** The props that show this record's fields. */
    reloadOnly: string[];
}) {
    const { t } = useLaravelReactI18n();

    const [phase, setPhaseState] = useState<CollaborativeFieldPhase>('viewing');
    const [value, setValueState] = useState(field.value);
    const [saveStatus, setSaveStatus] =
        useState<CollaborativeFieldSaveStatus>('idle');
    // The text just saved, shown until the prop has caught up with it.
    const [settling, setSettling] = useState<string | null>(null);
    const [error, setErrorState] = useState<string | null>(null);
    const [conflictMessage, setConflictMessage] = useState<string | null>(null);

    // Separate instances: each has its own request in flight.
    const lockHttp = useHttp();
    const heartbeatHttp = useHttp();
    const saveHttp = useHttp<{ value: string }>({ value: '' });

    const phaseRef = useRef<CollaborativeFieldPhase>('viewing');
    const valueRef = useRef(field.value);
    // The value the server last confirmed while this tab held the field.
    const serverValueRef = useRef(field.value);
    const dirtyRef = useRef(false);
    const savingRef = useRef<Promise<void> | null>(null);
    const debounceRef = useRef<number | null>(null);
    const lastEditRef = useRef(0);
    const recoveringRef = useRef(false);
    const errorRef = useRef<string | null>(null);

    const args = { type: field.type, id: field.id, field: field.field };
    const urls = {
        lock: claimRoute.url(args),
        heartbeat: heartbeatRoute.url(args),
        release: releaseRoute.url(args),
        save: saveRoute.url(args),
    };

    const lockedByOther = field.lock !== null && !field.lock.isThisTab;
    const holdsField = phase === 'editing' || phase === 'offline';

    function setPhase(next: CollaborativeFieldPhase): void {
        phaseRef.current = next;
        setPhaseState(next);
    }

    function setError(next: string | null): void {
        errorRef.current = next;
        setErrorState(next);
    }

    function setValue(next: string): void {
        valueRef.current = next;
        setValueState(next);
    }

    function clearDebounce(): void {
        if (debounceRef.current !== null) {
            window.clearTimeout(debounceRef.current);
            debounceRef.current = null;
        }
    }

    function reload(onFinish?: () => void): void {
        router.reload({ only: reloadOnly, onFinish });
    }

    async function releaseQuietly(): Promise<void> {
        try {
            await lockHttp.delete(urls.release);
        } catch {
            // Best effort: an unreleased field expires on its own.
        }
    }

    function goOffline(): void {
        if (phaseRef.current !== 'editing') {
            return;
        }

        clearDebounce();
        setPhase('offline');

        if (dirtyRef.current) {
            setSaveStatus('unsaved');
        }
    }

    async function enterConflict(message: string | null, holdsLock: boolean) {
        clearDebounce();
        setPhase('conflict');
        setSaveStatus('unsaved');
        setConflictMessage(
            message ?? t('collaboration.field.conflict_fallback'),
        );

        if (holdsLock) {
            await releaseQuietly();
        }
    }

    async function expire(): Promise<void> {
        clearDebounce();
        setPhase('expired');
        setSaveStatus('idle');
        await releaseQuietly();
        reload();
    }

    /** The lock is gone (heartbeat or save got 409): expired, or resume. */
    async function lostLock(): Promise<void> {
        const idleFor = Date.now() - lastEditRef.current;

        // The server counts from its own clock: a few seconds of slack.
        if (idleFor >= IDLE_SECONDS * 1000 - 5000) {
            await expire();

            return;
        }

        goOffline();
        await recover();
    }

    async function flush(): Promise<void> {
        while (dirtyRef.current && phaseRef.current === 'editing') {
            dirtyRef.current = false;
            setSaveStatus('saving');

            const sent = valueRef.current;
            saveHttp.transform(() => ({ value: sent }));

            try {
                await saveHttp.put(urls.save);

                serverValueRef.current = sent;
                setError(null);
                setSaveStatus(dirtyRef.current ? 'pending' : 'saved');
            } catch (failure) {
                const status = statusOf(failure);

                if (status === null) {
                    // Not saved: keep it dirty so the way back saves it.
                    dirtyRef.current = true;
                    goOffline();
                } else if (status === 409) {
                    dirtyRef.current = true;
                    window.setTimeout(() => void lostLock(), 0);
                } else if (status === 422) {
                    setError(bodyOf(failure).errors?.value?.[0] ?? null);
                    setSaveStatus('unsaved');
                } else {
                    dirtyRef.current = true;
                    setSaveStatus('unsaved');
                }

                return;
            }
        }
    }

    /** One save at a time; whoever types during a save gets one more after it. */
    function save(): Promise<void> {
        savingRef.current ??= flush().finally(() => {
            savingRef.current = null;
        });

        return savingRef.current;
    }

    async function claim(): Promise<void> {
        if (
            !field.canEdit ||
            lockedByOther ||
            phaseRef.current === 'claiming' ||
            phaseRef.current === 'editing' ||
            phaseRef.current === 'offline' ||
            phaseRef.current === 'conflict'
        ) {
            return;
        }

        setPhase('claiming');
        setConflictMessage(null);
        setError(null);

        try {
            const claimed = (await lockHttp.post(
                urls.lock,
            )) as CollaborativeFieldData;

            serverValueRef.current = claimed.value;
            lastEditRef.current = Date.now();

            if (!dirtyRef.current) {
                setValue(claimed.value);
            }

            setPhase('editing');
            setSaveStatus(dirtyRef.current ? 'pending' : 'idle');

            if (dirtyRef.current) {
                await save();
            }
        } catch (failure) {
            dirtyRef.current = false;
            setPhase('viewing');
            setValue(field.value);
            toast.error(
                bodyOf(failure).message ??
                    t('collaboration.field.claim_failed'),
            );
            // Someone else may hold it now: show who.
            reload();
        }
    }

    function change(next: string): void {
        if (
            phaseRef.current !== 'claiming' &&
            phaseRef.current !== 'editing' &&
            phaseRef.current !== 'offline'
        ) {
            return;
        }

        setValue(next);
        dirtyRef.current = true;
        lastEditRef.current = Date.now();
        setSaveStatus(phaseRef.current === 'offline' ? 'unsaved' : 'pending');
        clearDebounce();

        if (phaseRef.current === 'editing') {
            debounceRef.current = window.setTimeout(() => {
                debounceRef.current = null;
                void save();
            }, AUTOSAVE_DEBOUNCE_MS);
        }
    }

    /** Leaving the field: finish the pending save, then release. */
    async function done(): Promise<void> {
        if (phaseRef.current !== 'editing') {
            return;
        }

        clearDebounce();
        await save();

        if (phaseRef.current !== 'editing') {
            return;
        }

        if (
            errorRef.current !== null ||
            valueRef.current !== serverValueRef.current
        ) {
            // The last text was refused (say, an empty name): it is not kept.
            toast.error(errorRef.current ?? t('collaboration.field.unsaved'));
        }

        dirtyRef.current = false;
        // Show the saved text until the reload brings it as the prop.
        setSettling(serverValueRef.current);
        setPhase('viewing');
        setSaveStatus('idle');
        setError(null);
        await releaseQuietly();
        reload(() => setSettling(null));
    }

    /** The way back after a lost connection. */
    async function recover(): Promise<void> {
        if (phaseRef.current !== 'offline' || recoveringRef.current) {
            return;
        }

        recoveringRef.current = true;

        try {
            const claimed = (await lockHttp.post(
                urls.lock,
            )) as CollaborativeFieldData;

            if (claimed.value !== serverValueRef.current) {
                // Changed by someone else while this tab was away.
                await enterConflict(null, true);

                return;
            }

            lastEditRef.current = Date.now();
            setPhase('editing');
            setSaveStatus(dirtyRef.current ? 'pending' : 'saved');
            await save();
        } catch (failure) {
            if (statusOf(failure) !== null) {
                await enterConflict(bodyOf(failure).message ?? null, false);
            }
        } finally {
            recoveringRef.current = false;
        }
    }

    function reloadAfterConflict(): void {
        dirtyRef.current = false;
        setConflictMessage(null);
        setSaveStatus('idle');
        setPhase('viewing');
        reload();
    }

    async function heartbeat(): Promise<void> {
        try {
            await heartbeatHttp.put(urls.heartbeat);
        } catch (failure) {
            if (phaseRef.current !== 'editing') {
                return;
            }

            if (statusOf(failure) === 409) {
                await lostLock();
            } else if (statusOf(failure) === null) {
                goOffline();
            }
        }
    }

    /** Leaving the page while holding the field. */
    function leave(): void {
        if (phaseRef.current !== 'editing' && phaseRef.current !== 'offline') {
            return;
        }

        clearDebounce();
        phaseRef.current = 'viewing';

        const release = () => keepaliveRequest(urls.release, 'DELETE');

        if (!dirtyRef.current) {
            void release();

            return;
        }

        // Save first; the release follows if the page lives long enough,
        // otherwise the session expires with the text saved.
        void keepaliveRequest(urls.save, 'PUT', {
            value: valueRef.current,
        }).then(release);
    }

    // While editing, a prop that no longer shows this tab's lock (closed by
    // the scheduler, say) is checked with a heartbeat: a 409 takes the way out.
    const onFieldProp = useEffectEvent((isThisTab: boolean) => {
        if (phaseRef.current === 'editing' && !isThisTab) {
            void heartbeat();
        }
    });

    useEffect(() => {
        onFieldProp(field.lock?.isThisTab ?? false);
    }, [field.lock]);

    // The heartbeat: on an interval, and at once when the tab is visible again.
    const onHeartbeat = useEffectEvent(() => void heartbeat());
    const isEditing = phase === 'editing';

    useEffect(() => {
        if (!isEditing) {
            return;
        }

        const interval = window.setInterval(
            onHeartbeat,
            HEARTBEAT_SECONDS * 1000,
        );
        const onVisibility = () => {
            if (document.visibilityState === 'visible') {
                onHeartbeat();
            }
        };

        document.addEventListener('visibilitychange', onVisibility);

        return () => {
            window.clearInterval(interval);
            document.removeEventListener('visibilitychange', onVisibility);
        };
    }, [isEditing]);

    // Offline: retry on an interval, when the browser is back online, and
    // when the socket reconnects.
    const onRetry = useEffectEvent(() => void recover());
    const isConnectionLost = useConnectionRecovery(() => void recover());
    const onConnectionLost = useEffectEvent(() => goOffline());

    useEffect(() => {
        if (isConnectionLost) {
            onConnectionLost();
        }
    }, [isConnectionLost]);

    useEffect(() => {
        if (phase !== 'offline') {
            return;
        }

        const interval = window.setInterval(() => {
            if (!isConnectionLost) {
                onRetry();
            }
        }, HEARTBEAT_SECONDS * 1000);

        window.addEventListener('online', onRetry);

        return () => {
            window.clearInterval(interval);
            window.removeEventListener('online', onRetry);
        };
    }, [phase, isConnectionLost]);

    // Release when leaving: a page visit (not a partial reload or a
    // prefetch), closing the tab, or this field going away.
    const onLeave = useEffectEvent(() => leave());

    useEffect(() => {
        if (!holdsField) {
            return;
        }

        const removeBefore = router.on('before', (event) => {
            const visit = event.detail.visit;

            if (visit.only.length === 0 && !visit.prefetch) {
                onLeave();
            }
        });

        window.addEventListener('pagehide', onLeave);

        return () => {
            removeBefore();
            window.removeEventListener('pagehide', onLeave);
        };
    }, [holdsField]);

    useEffect(() => () => onLeave(), []);

    // A tab that does not hold the field follows the prop.
    const following = phase === 'viewing' || phase === 'expired';

    return {
        phase,
        value: following ? (settling ?? field.value) : value,
        saveStatus,
        error,
        conflictMessage,
        lockedByOther,
        /** After a conflict the local text stays for copying, not typing. */
        readOnly: phase === 'conflict',
        claim,
        change,
        done,
        reloadAfterConflict,
    };
}
