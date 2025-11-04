<?php

namespace App\Policies;

use App\Models\Design;
use App\Models\User;

class DesignPolicy
{
    /**
     * Determine if the user can view any designs.
     */
    public function viewAny(User $user): bool
    {
        return true; // Users can view designs in their organizations
    }

    /**
     * Determine if the user can view the design.
     */
    public function view(User $user, Design $design): bool
    {
        return $user->hasAccessToOrganization($design->organization_id)
            && $user->canInOrganization('view-designs', $design->organization_id);
    }

    /**
     * Determine if the user can create designs.
     */
    public function create(User $user, ?string $organizationId = null): bool
    {
        if (! $organizationId) {
            return false;
        }

        return $user->hasAccessToOrganization($organizationId)
            && $user->canInOrganization('create-designs', $organizationId);
    }

    /**
     * Determine if the user can update the design.
     */
    public function update(User $user, Design $design): bool
    {
        return $user->hasAccessToOrganization($design->organization_id)
            && $user->canInOrganization('update-designs', $design->organization_id);
    }

    /**
     * Determine if the user can delete the design.
     */
    public function delete(User $user, Design $design): bool
    {
        return $user->hasAccessToOrganization($design->organization_id)
            && $user->canInOrganization('delete-designs', $design->organization_id);
    }

    /**
     * Determine if the user can publish the design.
     */
    public function publish(User $user, Design $design): bool
    {
        return $user->hasAccessToOrganization($design->organization_id)
            && $user->canInOrganization('publish-designs', $design->organization_id);
    }
}
