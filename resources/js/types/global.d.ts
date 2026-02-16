import type { SharedData } from '@/types/generated';

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: SharedData & {
            [key: string]: unknown;
        };
    }
}
