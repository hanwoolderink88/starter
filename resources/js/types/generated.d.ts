declare namespace App.Features.Auth.Data {
export type AuthData = {
user: App.Features.Auth.Data.UserData;
};
export type ForgotPasswordPageData = {
status: string | null;
};
export type LoginPageData = {
canResetPassword: boolean;
canRegister: boolean;
status: string | null;
};
export type ResetPasswordPageData = {
token: string;
email: string | null;
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
export type VerifyEmailPageData = {
status: string | null;
};
export type WelcomePageData = {
canRegister: boolean;
};
}
declare namespace App.Features.Settings.Data {
export type ProfilePageData = {
mustVerifyEmail: boolean;
status: string | null;
};
export type TwoFactorPageData = {
twoFactorEnabled: boolean;
requiresConfirmation: boolean;
};
}
