import { echo } from '@laravel/echo-react';
import { useEffect, useRef, useState } from 'react';

/**
 * Track whether the Reverb connection was lost and run `onRecovered` once it
 * is back. Signals broadcast while the socket was down are gone for good,
 * so a caller refetches its data instead of waiting for them.
 *
 * The first connect after page load is not a recovery: only a connection that
 * was up, dropped and came back counts. Returns whether it is down right now.
 */
export function useConnectionRecovery(onRecovered?: () => void): boolean {
    const [isLost, setIsLost] = useState(false);

    // Subscribed once, so the callback is read from a ref, not a stale closure.
    const latestOnRecovered = useRef(onRecovered);

    useEffect(() => {
        latestOnRecovered.current = onRecovered;
    });

    useEffect(() => {
        const instance = echo();
        let hasConnected = instance.connectionStatus() === 'connected';
        let lost = false;

        return instance.connector.onConnectionChange((status) => {
            if (status === 'connected') {
                hasConnected = true;

                if (lost) {
                    lost = false;
                    setIsLost(false);
                    latestOnRecovered.current?.();
                }

                return;
            }

            if (hasConnected && !lost) {
                lost = true;
                setIsLost(true);
            }
        });
    }, []);

    return isLost;
}
