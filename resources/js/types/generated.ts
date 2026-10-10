export type ActivityCauserData = {
    id: number;
    name: string;
};
export type ActivityData = {
    id: number;
    event: ActivityEvent;
    subjectType: string | null;
    subjectId: number | null;
    causerId: number | null;
    causerName: string;
    impersonatorName: string | null;
    source: ActivitySource;
    changes: Record<string, unknown>;
    snapshot: Record<string, unknown> | null;
    ipAddress: string | null;
    userAgent: string | null;
    createdAt: string;
    createdAtDisplay: string;
};
export enum ActivityEvent {
    Created = 'created',
    Updated = 'updated',
    Deleted = 'deleted',
    Restored = 'restored',
    Anonymized = 'anonymized',
    StatusChanged = 'status_changed',
    RolesChanged = 'roles_changed',
    InvitationResent = 'invitation_resent',
    InvitationAccepted = 'invitation_accepted',
    ImpersonationStarted = 'impersonation_started',
    ImpersonationStopped = 'impersonation_stopped',
    LoggedIn = 'logged_in',
    LoggedOut = 'logged_out',
    LoginFailed = 'login_failed',
    LockedOut = 'locked_out',
    PasswordReset = 'password_reset',
    PasswordChanged = 'password_changed',
    OtherSessionsLoggedOut = 'other_sessions_logged_out',
    TwoFactorEnabled = 'two_factor_enabled',
    TwoFactorConfirmed = 'two_factor_confirmed',
    TwoFactorDisabled = 'two_factor_disabled',
    TwoFactorFailed = 'two_factor_failed',
    RecoveryCodesGenerated = 'recovery_codes_generated',
    RecoveryCodeUsed = 'recovery_code_used',
}
export type ActivityFiltersData = {
    event: ActivityEvent | null;
    source: ActivitySource | null;
    causerId: number | null;
    subjectType: string | null;
    subjectId: number | null;
    from: string | null;
    to: string | null;
};
export type ActivityLogEntryData = {
    activity: ActivityData;
    subjectName: string | null;
    subjectExists: boolean;
};
export type ActivityPageData = {
    entries: PaginatedDataCollection<number, ActivityLogEntryData>;
    filters: ActivityFiltersData;
    causerOptions: ActivityCauserData[];
    subjectTypeOptions: string[];
};
export enum ActivitySource {
    Web = 'web',
    Mcp = 'mcp',
    System = 'system',
}
export type AssignableRoleData = {
    id: number;
    name: string;
    isActive: boolean;
    isProtected: boolean;
};
export type AuthData = {
    user: UserData | null;
    impersonatorName: string | null;
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
    ManageRoles = 'manage roles',
    ViewActivity = 'view activity',
}
export enum PermissionGroup {
    Users = 'users',
    Roles = 'roles',
    Activity = 'activity',
}
export type PermissionGroupData = {
    group: PermissionGroup;
    permissions: Permission[];
};
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
export enum ResourceAction {
    Created = 'created',
    Updated = 'updated',
    Deleted = 'deleted',
}
export type ResourceChangedData = {
    action: ResourceAction;
    id: number;
    label: string;
    actorId: number | null;
    actorName: string;
    source: ActivitySource;
    origin: string | null;
    impersonatorName: string | null;
};
export type RoleData = {
    id: number;
    name: string;
    description: string | null;
    isActive: boolean;
    isProtected: boolean;
    permissionCount: number;
    userCount: number;
    version: number;
};
export type RoleFormPageData = {
    role: RoleData | null;
    permissionGroups: PermissionGroupData[];
    rolePermissions: Permission[];
    activity: ActivityData[] | null;
    holders: RoleHolderData[];
};
export type RoleHolderData = {
    id: number;
    name: string;
};
export type RolesPageData = {
    roles: RoleData[];
    permissionTotal: number;
    inactiveCount: number;
};
export type SessionData = {
    ipAddress: string | null;
    browser: string | null;
    platform: string | null;
    isDesktop: boolean;
    isCurrentDevice: boolean;
    lastActiveAt: string;
    lastActiveDisplay: string;
};
export type SessionsPageData = {
    sessions: SessionData[];
    available: boolean;
};
export type SharedData = {
    name: string;
    auth: AuthData;
    permissions: Permission[];
    sidebarOpen: boolean;
    locale: string;
    supportedLocales: string[];
};
export type ShowUserPageData = {
    user: UserManagementData;
    canUpdate: boolean;
    canImpersonate: boolean;
    activity: ActivityData[] | null;
};
export enum SortDirection {
    Asc = 'asc',
    Desc = 'desc',
}
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
export type UserFiltersData = {
    search: string | null;
    roleId: number | null;
    status: UserStatus | null;
};
export type UserFormPageData = {
    user: UserManagementData | null;
    roles: AssignableRoleData[];
    roleIds: number[];
    canAssignRoles: boolean;
    isLastAdministrator: boolean;
};
export type UserManagementData = {
    id: number;
    name: string;
    email: string;
    email_verified_at: string | null;
    created_at: string;
    created_at_display: string;
    roles: string[];
    has_password: boolean;
    version: number;
    anonymizes_on_display: string | null;
};
export enum UserSortColumn {
    Name = 'name',
    Email = 'email',
    CreatedAt = 'created_at',
}
export type UserSortData = {
    column: UserSortColumn;
    direction: SortDirection;
};
export enum UserStatus {
    Active = 'active',
    Invited = 'invited',
    Deleted = 'deleted',
}
export type UsersPageData = {
    users: PaginatedDataCollection<number, UserManagementData>;
    filters: UserFiltersData;
    sort: UserSortData;
    roleOptions: AssignableRoleData[];
    statusOptions: Record<string, string>;
    canCreate: boolean;
    impersonatableIds: number[];
    restoreDays: number;
};
export type VerifyEmailPageData = {
    status: string | null;
};
export type WelcomePageData = {
    canRegister: boolean;
};
