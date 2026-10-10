import { ActivitySource } from '@/types/generated';

type Translate = (key: string, replacements?: Record<string, string>) => string;

/**
 * One label for whoever made a change, shared by the activity log and the
 * co-working toasts: "Jane", "Jane, impersonated by Piet" or "Jane via Claude".
 */
export function actorLabel(
    t: Translate,
    {
        name,
        impersonatorName,
        source,
    }: {
        name: string;
        impersonatorName: string | null;
        source: ActivitySource;
    },
): string {
    if (impersonatorName) {
        return t('common.actor.impersonated', {
            actor: name,
            impersonator: impersonatorName,
        });
    }

    if (source === ActivitySource.Mcp) {
        return t('common.actor.mcp', { actor: name });
    }

    return name;
}
