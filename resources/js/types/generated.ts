export type AuthData = {
    user: UserData | null;
};
export type CursorPaginatedDataCollection<TKey, TValue> = CursorPaginator<
    TKey,
    TValue
>;
export type CursorPaginator<TKey, TValue> = {
    data: TKey extends string ? Record<TKey, TValue> : TValue[];
    links: {
        url: string | null;
        label: string;
        active: boolean;
    }[];
    meta: {
        path: string;
        per_page: number;
        next_cursor: string | null;
        next_page_url: string | null;
        prev_cursor: string | null;
        prev_page_url: string | null;
    };
};
export type CursorPaginatorInterface<TKey, TValue> = CursorPaginator<
    TKey,
    TValue
>;
export type ForgotPasswordPageData = {
    status: string | null;
};
export type LengthAwarePaginator<TKey, TValue> = {
    data: TKey extends string ? Record<TKey, TValue> : TValue[];
    links: {
        url: string | null;
        label: string;
        active: boolean;
    }[];
    meta: {
        total: number;
        current_page: number;
        first_page_url: string;
        from: number | null;
        last_page: number;
        last_page_url: string;
        next_page_url: string | null;
        path: string;
        per_page: number;
        prev_page_url: string | null;
        to: number | null;
    };
};
export type LengthAwarePaginatorInterface<TKey, TValue> = LengthAwarePaginator<
    TKey,
    TValue
>;
export type LoginPageData = {
    canResetPassword: boolean;
    canRegister: boolean;
    status: string | null;
};
export type PaginatedDataCollection<TKey, TValue> = LengthAwarePaginator<
    TKey,
    TValue
>;
export type PasswordPageData = {
    passwordRules: string;
};
export enum Permission {
    ViewUsers = 'view users',
    CreateUsers = 'create users',
    UpdateUsers = 'update users',
    DeleteUsers = 'delete users',
    ImpersonateUsers = 'impersonate users',
}
export type ProfilePageData = {
    mustVerifyEmail: boolean;
    status: string | null;
};
export type RegisterPageData = {
    passwordRules: string;
};
export type ResetPasswordPageData = {
    token: string;
    passwordRules: string;
    email: string | null;
};
export enum Role {
    User = 'user',
    SuperAdmin = 'super-admin',
}
export type SharedData = {
    name: string;
    auth: AuthData;
    permissions: Permission[];
    sidebarOpen: boolean;
};
export type TwoFactorPageData = {
    twoFactorEnabled: boolean;
    requiresConfirmation: boolean;
};
export type TwoFactorSecretKeyData = {
    secretKey: string;
};
export type TwoFactorSetupData = {
    svg: string;
    url: string;
};
export type UserData = {
    id: number;
    name: string;
    email: string;
    avatar: string | null;
    email_verified_at: string | null;
    two_factor_enabled: boolean | null;
    created_at: string;
    updated_at: string;
};
export type UserFormPageData = {
    user: UserManagementData | null;
    roles: Record<string, string>;
};
export type UserManagementData = {
    id: number;
    name: string;
    email: string;
    email_verified_at: string | null;
    created_at: string;
    created_at_display: string;
    role: string;
    has_password: boolean;
};
export type UsersPageData = {
    users: UserManagementData[];
    canCreate: boolean;
    canImpersonate: boolean;
};
export type VerifyEmailPageData = {
    status: string | null;
};
export type WelcomePageData = {
    canRegister: boolean;
};
