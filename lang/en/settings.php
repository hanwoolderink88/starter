<?php

declare(strict_types=1);

return [
    'title' => 'Settings',
    'description' => 'Manage your profile and account settings',

    'nav' => [
        'label' => 'Settings',
        'profile' => 'Profile',
        'password' => 'Password',
        'two_factor' => 'Two-Factor Auth',
        'appearance' => 'Appearance',
        'sessions' => 'Sessions',
    ],

    'profile' => [
        'title' => 'Profile settings',
        'heading' => 'Profile information',
        'description' => 'Update your name and email address',
        'name' => 'Name',
        'name_placeholder' => 'Full name',
        'email' => 'Email address',
        'email_placeholder' => 'Email address',
        'unverified' => 'Your email address is unverified.',
        'resend_verification' => 'Click here to resend the verification email.',
        'verification_sent' => 'A new verification link has been sent to your email address.',
        'save' => 'Save',
    ],

    'password' => [
        'title' => 'Password settings',
        'heading' => 'Update password',
        'description' => 'Ensure your account is using a long, random password to stay secure',
        'current' => 'Current password',
        'new' => 'New password',
        'confirm' => 'Confirm password',
        'save' => 'Save password',
    ],

    'sessions' => [
        'title' => 'Browser sessions',
        'heading' => 'Browser sessions',
        'description' => 'Where you are logged in. Log out everywhere else if you lost a device or do not recognise a session.',
        'unavailable' => 'Sessions can only be listed when they are kept in the database.',
        'this_device' => 'This device',
        'last_active' => 'Last active :time',
        'unknown_browser' => 'Unknown browser',
        'unknown_platform' => 'unknown system',
        'unknown_ip' => 'unknown address',
        'logout_others' => 'Log out other browser sessions',
        'dialog_title' => 'Log out other browser sessions',
        'dialog_description' => 'Enter your password to log out on all your other devices. This also ends "remember me" on those devices.',
        'password' => 'Password',
        'cancel' => 'Cancel',
        'confirm' => 'Log out other sessions',
        'logged_out' => 'You have been logged out on your other devices.',
    ],

    'appearance' => [
        'title' => 'Appearance settings',
        'description' => 'Update your account\'s appearance settings',
        'theme' => [
            'title' => 'Theme',
            'description' => 'Light, dark or follow your system',
        ],
        'options' => [
            'light' => 'Light',
            'dark' => 'Dark',
            'system' => 'System',
        ],
    ],

    'language' => [
        'title' => 'Language',
        'description' => 'Choose the language of the interface',
        'options' => [
            'en' => 'English',
            'nl' => 'Nederlands',
        ],
        'updated' => 'Language updated.',
        'blocked' => 'While impersonating, the language of this account cannot be changed.',
    ],

    'delete_account' => [
        'heading' => 'Delete account',
        'description' => 'Delete your account and all of its resources',
        'warning_title' => 'Warning',
        'warning' => 'You can no longer log in. An administrator can restore your account for a limited time; after that your personal data is anonymized for good.',
        'button' => 'Delete account',
        'dialog_title' => 'Are you sure you want to delete your account?',
        'dialog_description' => 'Once your account is deleted you are logged out and can no longer log in. After a limited time your personal data is anonymized for good. Enter your password to confirm.',
        'password' => 'Password',
        'cancel' => 'Cancel',
        'confirm' => 'Delete account',
    ],

    'two_factor' => [
        'title' => 'Two-Factor Authentication',
        'description' => 'Manage your two-factor authentication settings',
        'enabled' => 'Enabled',
        'disabled' => 'Disabled',
        'enabled_text' => 'With two-factor authentication enabled, you will be prompted for a secure, random pin during login, which you can retrieve from the TOTP-supported application on your phone.',
        'disabled_text' => 'When you enable two-factor authentication, you will be prompted for a secure pin during login. This pin can be retrieved from a TOTP-supported application on your phone.',
        'enable' => 'Enable 2FA',
        'disable' => 'Disable 2FA',
        'continue_setup' => 'Continue Setup',

        'recovery' => [
            'title' => '2FA Recovery Codes',
            'description' => 'Recovery codes let you regain access if you lose your 2FA device. Store them in a secure password manager.',
            'view' => 'View Recovery Codes',
            'hide' => 'Hide Recovery Codes',
            'regenerate' => 'Regenerate Codes',
            'list_label' => 'Recovery codes',
            'loading' => 'Loading recovery codes',
            'warning_before' => 'Each recovery code can be used once to access your account and will be removed after use. If you need more, click',
            'warning_after' => 'above.',
        ],

        'setup' => [
            'enabled_title' => 'Two-Factor Authentication Enabled',
            'enabled_description' => 'Two-factor authentication is now enabled. Scan the QR code or enter the setup key in your authenticator app.',
            'verify_title' => 'Verify Authentication Code',
            'verify_description' => 'Enter the 6-digit code from your authenticator app',
            'enable_title' => 'Enable Two-Factor Authentication',
            'enable_description' => 'To finish enabling two-factor authentication, scan the QR code or enter the setup key in your authenticator app',
            'manual' => 'or, enter the code manually',
            'copy_key' => 'Copy setup key',
            'close' => 'Close',
            'continue' => 'Continue',
            'back' => 'Back',
            'confirm' => 'Confirm',
        ],

        'errors' => [
            'qr_code' => 'Failed to fetch QR code',
            'setup_key' => 'Failed to fetch a setup key',
            'recovery_codes' => 'Failed to fetch recovery codes',
        ],
    ],

    'flash' => [
        'profile_updated' => 'Profile updated.',
        'password_updated' => 'Password updated.',
    ],
];
