export type AuthData = {
    user: UserData | null;
};
export type ForgotPasswordPageData = {
    status: string | null;
};
export type LoginPageData = {
    canResetPassword: boolean;
    canRegister: boolean;
    status: string | null;
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
export type ResetPasswordPageData = {
    token: string;
    email: string | null;
};
export enum Role {
    User = 'user',
    SuperAdmin = 'super-admin',
}
export type SharedData = {
    name: string;
    auth: AuthData;
    permissions: Array<Permission>;
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
    roles: Array<any>;
};
export type UserManagementData = {
    id: number;
    name: string;
    email: string;
    email_verified_at: string | null;
    created_at: string;
    role: string;
    has_password: boolean;
};
export type UsersPageData = {
    users: any;
    canCreate: boolean;
    canImpersonate: boolean;
};
export type VerifyEmailPageData = {
    status: string | null;
};
export type WelcomePageData = {
    canRegister: boolean;
};
