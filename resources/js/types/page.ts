import type { SharedData } from '@/types/generated';

export type PageProps<T = object> = SharedData & T;
