<?php

namespace App\Models;

use App\Enums\DesignStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Design extends Model implements HasMedia
{
    use HasFactory, HasUuids, InteractsWithMedia;

    protected $fillable = [
        'organization_id',
        'creator_id',
        'name',
        'description',
        'design_data',
        'variables',
        'settings',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'design_data' => 'array',
            'variables' => 'array',
            'settings' => 'array',
            'status' => DesignStatus::class,
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class);
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    public function isActive(): bool
    {
        return $this->status === DesignStatus::Active;
    }

    public function isDraft(): bool
    {
        return $this->status === DesignStatus::Draft;
    }

    public function duplicate(): self
    {
        $duplicate = $this->replicate();
        $duplicate->name = $this->name.' (Copy)';
        $duplicate->status = DesignStatus::Draft;
        $duplicate->save();

        return $duplicate;
    }

    public function scopeForOrganization($query, string $organizationId)
    {
        return $query->where('organization_id', $organizationId);
    }

    public function scopeActive($query)
    {
        return $query->where('status', DesignStatus::Active);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('preview_image')
            ->singleFile();
    }
}
