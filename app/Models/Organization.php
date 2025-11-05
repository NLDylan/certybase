<?php

namespace App\Models;

use App\Enums\OrganizationStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Cashier\Subscription as CashierSubscription;
use Laravel\Cashier\Billable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Organization extends Model implements HasMedia
{
    use Billable, HasFactory, HasUuids, InteractsWithMedia;

    protected $fillable = [
        'name',
        'description',
        'email',
        'phone_number',
        'website',
        'status',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrganizationStatus::class,
            'settings' => 'array',
        ];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'organization_user')
            ->using(OrganizationUser::class)
            ->withPivot(['status', 'invited_at', 'accepted_at', 'invitation_token', 'invitation_expires_at', 'invited_role'])
            ->withTimestamps();
    }

    public function organizationMemberships(): HasMany
    {
        return $this->hasMany(OrganizationUser::class);
    }

    public function designs(): HasMany
    {
        return $this->hasMany(Design::class);
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class);
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    /**
     * Override Cashier's default foreign key guess (organization_id) and
     * intentionally use the standard 'user_id' column on subscriptions.
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(CashierSubscription::class, 'user_id')->orderBy('created_at', 'desc');
    }

    public function isActive(): bool
    {
        return $this->status === OrganizationStatus::Active;
    }

    public function isSuspended(): bool
    {
        return $this->status === OrganizationStatus::Suspended;
    }

    public function hasActiveSubscription(): bool
    {
        return $this->subscribed() || $this->onTrial();
    }

    public function canCreateMoreCertificates(): bool
    {
        // TODO: Implement based on subscription limits
        return $this->hasActiveSubscription();
    }

    public function scopeActive($query)
    {
        return $query->where('status', OrganizationStatus::Active);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')
            ->singleFile();
    }
}
