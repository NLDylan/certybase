<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements HasMedia
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasRoles, HasUuids, InteractsWithMedia, Notifiable, TwoFactorAuthenticatable;

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone_number',
        'is_admin',
        'is_onboarded',
        'onboarded_at',
        'wants_organization',
        'profile_completed',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
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
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'is_admin' => 'boolean',
            'is_onboarded' => 'boolean',
            'onboarded_at' => 'datetime',
            'wants_organization' => 'boolean',
            'profile_completed' => 'boolean',
        ];
    }

    public function organizationMemberships(): HasMany
    {
        return $this->hasMany(OrganizationUser::class);
    }

    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class, 'organization_user')
            ->using(OrganizationUser::class)
            ->withPivot(['status', 'invited_at', 'accepted_at', 'invitation_token', 'invitation_expires_at', 'invited_role'])
            ->withTimestamps();
    }

    public function createdDesigns(): HasMany
    {
        return $this->hasMany(Design::class, 'creator_id');
    }

    public function createdCampaigns(): HasMany
    {
        return $this->hasMany(Campaign::class, 'creator_id');
    }

    public function issuedCertificates(): HasMany
    {
        return $this->hasMany(Certificate::class, 'issued_to_user_id');
    }

    public function isAdmin(): bool
    {
        return $this->is_admin === true;
    }

    public function currentOrganization(): ?Organization
    {
        $organizationId = Session::get('organization_id');

        if (! $organizationId) {
            return $this->organizations()->first();
        }

        return $this->organizations()->where('organizations.id', $organizationId)->first();
    }

    public function switchOrganization(string $organizationId): void
    {
        if ($this->hasAccessToOrganization($organizationId)) {
            Session::put('organization_id', $organizationId);
        }
    }

    public function hasAccessToOrganization(string $organizationId): bool
    {
        return $this->organizationMemberships()
            ->where('organization_id', $organizationId)
            ->where('status', \App\Enums\OrganizationUserStatus::Active)
            ->exists();
    }

    public function hasRoleInOrganization(string $role, string $organizationId): bool
    {
        $originalTeamId = $this->getPermissionsTeamId();
        $this->setPermissionsTeamId($organizationId);

        $hasRole = $this->hasRole($role);

        $this->setPermissionsTeamId($originalTeamId);

        return $hasRole;
    }

    public function canInOrganization(string $permission, string $organizationId): bool
    {
        $originalTeamId = $this->getPermissionsTeamId();
        $this->setPermissionsTeamId($organizationId);

        $can = $this->can($permission);

        $this->setPermissionsTeamId($originalTeamId);

        return $can;
    }

    public function initials(): string
    {
        $words = explode(' ', $this->name);
        $initials = '';

        foreach ($words as $word) {
            if (! empty($word)) {
                $initials .= Str::upper(Str::substr($word, 0, 1));
            }
        }

        return Str::limit($initials, 2, '');
    }

    public function hasCompletedOnboarding(): bool
    {
        return $this->is_onboarded === true;
    }

    public function markAsOnboarded(): void
    {
        $this->update([
            'is_onboarded' => true,
            'onboarded_at' => now(),
        ]);
    }

    public function needsOnboarding(): bool
    {
        return ! $this->is_onboarded;
    }

    public function hasCompleteProfile(): bool
    {
        return $this->profile_completed === true;
    }

    public function wantsOrganization(): bool
    {
        return $this->wants_organization === true;
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')
            ->singleFile();
    }
}
