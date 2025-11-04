<?php

namespace App\Models;

use App\Enums\CertificateStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Certificate extends Model implements HasMedia
{
    use HasFactory, HasUuids, InteractsWithMedia;

    protected $fillable = [
        'organization_id',
        'design_id',
        'campaign_id',
        'issued_to_user_id',
        'recipient_name',
        'recipient_email',
        'recipient_data',
        'certificate_data',
        'verification_token',
        'status',
        'issued_at',
        'expires_at',
        'revoked_at',
        'revocation_reason',
    ];

    protected function casts(): array
    {
        return [
            'recipient_data' => 'array',
            'certificate_data' => 'array',
            'status' => CertificateStatus::class,
            'issued_at' => 'datetime',
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function ($certificate) {
            if (! $certificate->verification_token) {
                $certificate->verification_token = Str::random(64);
            }
        });
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function design(): BelongsTo
    {
        return $this->belongsTo(Design::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function issuedToUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_to_user_id');
    }

    public function isIssued(): bool
    {
        return $this->status === CertificateStatus::Issued;
    }

    public function isRevoked(): bool
    {
        return $this->status === CertificateStatus::Revoked;
    }

    public function revoke(?string $reason = null): void
    {
        $this->update([
            'status' => CertificateStatus::Revoked,
            'revoked_at' => now(),
            'revocation_reason' => $reason,
        ]);
    }

    public function issue(): void
    {
        $this->update([
            'status' => CertificateStatus::Issued,
            'issued_at' => now(),
        ]);
    }

    public function generateVerificationToken(): string
    {
        $token = Str::random(64);

        $this->update([
            'verification_token' => $token,
        ]);

        return $token;
    }

    public function getVerificationUrl(): string
    {
        return route('certificates.verify', ['token' => $this->verification_token]);
    }

    public function generatePDF(): void
    {
        // TODO: Implement PDF generation from certificate_data
        // This should be queued and stored in the certificate_pdf collection
    }

    public function scopeForOrganization($query, string $organizationId)
    {
        return $query->where('organization_id', $organizationId);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('certificate_pdf')
            ->singleFile();
    }
}
