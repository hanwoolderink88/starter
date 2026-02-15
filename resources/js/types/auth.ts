export type User = Omit<
    App.Features.Auth.Data.UserData,
    'avatar' | 'two_factor_enabled'
> & {
    avatar?: string;
    two_factor_enabled?: boolean;
    [key: string]: unknown;
};
export type Auth = Omit<App.Features.Auth.Data.AuthData, 'user'> & {
    user: User;
};
export type TwoFactorSetupData = App.Features.Auth.Data.TwoFactorSetupData;
export type TwoFactorSecretKey = App.Features.Auth.Data.TwoFactorSecretKeyData;
