import type {
    TwoFactorSecretKeyData,
    TwoFactorSetupData as GeneratedTwoFactorSetupData,
    UserData,
} from '@/types/generated';

export type User = UserData;
export type TwoFactorSetupData = GeneratedTwoFactorSetupData;
export type TwoFactorSecretKey = TwoFactorSecretKeyData;
