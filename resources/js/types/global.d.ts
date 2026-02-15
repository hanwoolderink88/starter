import type { Auth } from '@/types/auth';

type Permissions = {
    viewUsers: boolean;
};

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            permissions: Permissions;
            sidebarOpen: boolean;
            [key: string]: unknown;
        };
    }
}
