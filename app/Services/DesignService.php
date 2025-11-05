<?php

namespace App\Services;

use App\Enums\DesignStatus;
use App\Models\Design;
use App\Models\DesignTemplate;

class DesignService
{
    /**
     * Create a new design.
     */
    public function create(string $organizationId, string $userId, array $data): Design
    {
        return Design::create([
            'organization_id' => $organizationId,
            'creator_id' => $userId,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'design_data' => $data['design_data'] ?? null,
            'variables' => $data['variables'] ?? [],
            'settings' => $data['settings'] ?? [],
            'status' => $data['status'] ?? DesignStatus::Draft,
        ]);
    }

    /**
     * Duplicate an existing design.
     */
    public function duplicate(string $designId): Design
    {
        $design = Design::findOrFail($designId);
        $duplicate = $design->duplicate();

        return $duplicate;
    }

    /**
     * Create a design from a template.
     */
    public function createFromTemplate(string $templateId, string $organizationId, string $userId): Design
    {
        $template = DesignTemplate::findOrFail($templateId);

        return $template->createDesignFromTemplate($organizationId, $userId);
    }

    /**
     * Update design data (Fabric.js canvas data).
     */
    public function updateDesignData(string $designId, array $designData): Design
    {
        $design = Design::findOrFail($designId);
        $design->update([
            'design_data' => $designData,
        ]);

        return $design;
    }
}

