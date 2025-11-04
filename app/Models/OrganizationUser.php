<?php

namespace App\Models;

use App\Enums\OrganizationUserStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Str;

class OrganizationUser extends Pivot
{
    protected $table = 'organization_user';

    protected $fillable = [
        'organization_id',
        'user_id',
        'status',
        'invited_at',
        'accepted_at',
        'invitation_token',
        'invitation_expires_at',
        'invited_role',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrganizationUserStatus::class,
            'invited_at' => 'datetime',
            'accepted_at' => 'datetime',
            'invitation_expires_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isActive(): bool
    {
        return $this->status === OrganizationUserStatus::Active;
    }

    public function isPending(): bool
    {
        return $this->status === OrganizationUserStatus::Pending;
    }

    public function isInvitationExpired(): bool
    {
        if (! $this->invitation_expires_at) {
            return false;
        }

        return $this->invitation_expires_at->isPast();
    }

    public function acceptInvitation(): void
    {
        $this->update([
            'status' => OrganizationUserStatus::Active,
            'accepted_at' => now(),
        ]);
    }

    public function generateInvitationToken(): string
    {
        $token = Str::random(64);

        $this->update([
            'invitation_token' => $token,
            'invitation_expires_at' => now()->addDays(7),
        ]);

        return $token;
    }
}
