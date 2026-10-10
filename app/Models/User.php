<?php

declare(strict_types=1);

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Infrastructure\Activity\Concerns\HasVersion;
use Carbon\CarbonImmutable;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Passport\Contracts\OAuthenticatable;
use Laravel\Passport\HasApiTokens;
use Override;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property CarbonImmutable|null $email_verified_at
 * @property string|null $locale
 * @property int $version
 * @property CarbonImmutable|null $deleted_at
 * @property CarbonImmutable|null $anonymized_at
 */
class User extends Authenticatable implements HasLocalePreference, OAuthenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, HasVersion, Notifiable, SoftDeletes, TwoFactorAuthenticatable;

    /**
     * The guard used to resolve this model's roles and permissions.
     *
     * Pinned so Spatie resolves against the `web` guard (where all roles and
     * permissions are defined) even when a request is authenticated via the
     * Passport `api` guard, e.g. an MCP tool call.
     */
    protected string $guard_name = 'web';

    /**
     * Matches the column default, so a user created in this request already
     * knows their first version.
     *
     * @var array<string, mixed>
     */
    #[Override]
    protected $attributes = [
        'version' => 1,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    #[Override]
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    #[Override]
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'anonymized_at' => 'datetime',
        ];
    }

    /**
     * The language of the notifications sent to this user.
     */
    public function preferredLocale(): ?string
    {
        return $this->locale;
    }
}
