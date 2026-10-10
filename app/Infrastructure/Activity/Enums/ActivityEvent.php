<?php

declare(strict_types=1);

namespace App\Infrastructure\Activity\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * What happened in a log entry. A feature that needs a new kind of event adds
 * a case here and its label under `activity.events.{value}`.
 */
#[TypeScript]
enum ActivityEvent: string
{
    case Created = 'created';
    case Updated = 'updated';
    case Deleted = 'deleted';
    case Restored = 'restored';
    case Anonymized = 'anonymized';
    case StatusChanged = 'status_changed';
    case RolesChanged = 'roles_changed';

    case InvitationResent = 'invitation_resent';
    case InvitationAccepted = 'invitation_accepted';

    case ImpersonationStarted = 'impersonation_started';
    case ImpersonationStopped = 'impersonation_stopped';

    case LoggedIn = 'logged_in';
    case LoggedOut = 'logged_out';
    case LoginFailed = 'login_failed';
    case LockedOut = 'locked_out';
    case PasswordReset = 'password_reset';
    case PasswordChanged = 'password_changed';
    case OtherSessionsLoggedOut = 'other_sessions_logged_out';
    case TwoFactorEnabled = 'two_factor_enabled';
    case TwoFactorConfirmed = 'two_factor_confirmed';
    case TwoFactorDisabled = 'two_factor_disabled';
    case TwoFactorFailed = 'two_factor_failed';
    case RecoveryCodesGenerated = 'recovery_codes_generated';
    case RecoveryCodeUsed = 'recovery_code_used';
}
