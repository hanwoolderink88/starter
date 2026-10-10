<?php

declare(strict_types=1);

return [
    'failed' => 'These credentials do not match our records.',
    'password' => 'The provided password is incorrect.',
    'throttle' => 'Too many login attempts. Please try again in :seconds seconds.',

    'fields' => [
        'name' => 'Name',
        'name_placeholder' => 'Full name',
        'email' => 'Email address',
        'email_placeholder' => 'email@example.com',
        'password' => 'Password',
        'password_confirmation' => 'Confirm password',
        'remember' => 'Remember me',
    ],

    'password_input' => [
        'show' => 'Show password',
        'hide' => 'Hide password',
    ],

    'login' => [
        'head' => 'Log in',
        'title' => 'Log in to your account',
        'description' => 'Enter your email and password below to log in',
        'forgot_password' => 'Forgot password?',
        'submit' => 'Log in',
        'no_account' => 'Don\'t have an account?',
        'sign_up' => 'Sign up',
    ],

    'register' => [
        'head' => 'Register',
        'title' => 'Create an account',
        'description' => 'Enter your details below to create your account',
        'submit' => 'Create account',
        'has_account' => 'Already have an account?',
        'log_in' => 'Log in',
    ],

    'forgot_password' => [
        'head' => 'Forgot password',
        'title' => 'Forgot password',
        'description' => 'Enter your email to receive a password reset link',
        'submit' => 'Email password reset link',
        'return_to' => 'Or, return to',
        'log_in' => 'log in',
    ],

    'reset_password' => [
        'head' => 'Reset password',
        'title' => 'Reset password',
        'description' => 'Please enter your new password below',
        'email' => 'Email',
        'submit' => 'Reset password',
    ],

    'verify_email' => [
        'head' => 'Email verification',
        'title' => 'Verify email',
        'description' => 'Please verify your email address by clicking on the link we just emailed to you.',
        'link_sent' => 'A new verification link has been sent to the email address you provided during registration.',
        'resend' => 'Resend verification email',
        'logout' => 'Log out',
    ],

    'confirm_password' => [
        'head' => 'Confirm password',
        'title' => 'Confirm your password',
        'description' => 'This is a secure area of the application. Please confirm your password before continuing.',
        'submit' => 'Confirm password',
    ],

    'two_factor' => [
        'head' => 'Two-Factor Authentication',
        'submit' => 'Continue',
        'or' => 'or you can',
        'code' => [
            'title' => 'Authentication Code',
            'description' => 'Enter the authentication code provided by your authenticator application.',
            'use_instead' => 'log in using an authentication code',
        ],
        'recovery' => [
            'title' => 'Recovery Code',
            'description' => 'Please confirm access to your account by entering one of your emergency recovery codes.',
            'placeholder' => 'Enter recovery code',
            'use_instead' => 'log in using a recovery code',
        ],
    ],

    'invitation' => [
        'accept' => [
            'head' => 'Accept Invitation',
            'title' => 'Welcome, :name!',
            'description' => 'Please set a password to complete your account setup.',
            'submit' => 'Complete Setup',
            'processing' => 'Setting Password...',
        ],
        'expired' => [
            'head' => 'Invitation Expired',
            'title' => 'Invitation Link Expired',
            'description' => 'This invitation link has expired or is invalid.',
            'contact_admin' => 'Please contact your administrator for a new invitation.',
        ],
    ],
];
