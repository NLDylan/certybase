<?php

namespace App\Models;

use App\Enums\CampaignCompletionReason;
use App\Enums\CampaignStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campaign extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'organization_id',
        'design_id',
        'creator_id',
        'name',
        'description',
        'variable_mapping',
        'status',
        'start_date',
        'end_date',
        'certificate_limit',
        'certificates_issued',
        'completed_at',
        'completion_reason',
    ];

    protected function casts(): array
    {
        return [
            'variable_mapping' => 'array',
            'status' => CampaignStatus::class,
            'start_date' => 'date',
            'end_date' => 'date',
            'completed_at' => 'datetime',
            'completion_reason' => CampaignCompletionReason::class,
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function design(): BelongsTo
    {
        return $this->belongsTo(Design::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    public function isActive(): bool
    {
        return $this->status === CampaignStatus::Active;
    }

    public function isCompleted(): bool
    {
        return $this->status === CampaignStatus::Completed;
    }

    public function canIssueMore(): bool
    {
        if ($this->certificate_limit === null) {
            return true;
        }

        return $this->certificates_issued < $this->certificate_limit;
    }

    public function markAsCompleted(CampaignCompletionReason $reason): void
    {
        $this->update([
            'status' => CampaignStatus::Completed,
            'completed_at' => now(),
            'completion_reason' => $reason,
        ]);
    }

    public function incrementCertificatesIssued(): void
    {
        $this->increment('certificates_issued');
    }

    public function scopeForOrganization($query, string $organizationId)
    {
        return $query->where('organization_id', $organizationId);
    }
}
