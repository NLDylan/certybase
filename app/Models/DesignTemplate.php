<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class DesignTemplate extends Model implements HasMedia
{
    use HasFactory, HasUuids, InteractsWithMedia;

    protected $fillable = [
        'name',
        'description',
        'template_data',
        'variables',
        'category',
        'is_active',
        'is_public',
        'usage_count',
    ];

    protected function casts(): array
    {
        return [
            'template_data' => 'array',
            'variables' => 'array',
            'is_active' => 'boolean',
            'is_public' => 'boolean',
        ];
    }

    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    public function createDesignFromTemplate(string $organizationId, string $userId): Design
    {
        $this->incrementUsage();

        return Design::create([
            'organization_id' => $organizationId,
            'creator_id' => $userId,
            'name' => $this->name,
            'description' => $this->description,
            'design_data' => $this->template_data,
            'variables' => $this->variables,
            'status' => \App\Enums\DesignStatus::Draft,
        ]);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('preview_image')
            ->singleFile();
    }
}
