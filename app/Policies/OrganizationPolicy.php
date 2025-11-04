<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\User;

class OrganizationPolicy
{
    /**
     * Determine if the user can view any organizations.
     */
    public function viewAny(User $user): bool
    {
        return true; // Users can always view their own organizations
    }

    /**
     * Determine if the user can view the organization.
     */
    public function view(User $user, Organization $organization): bool
    {
        return $user->hasAccessToOrganization($organization->id);
    }

    /**
     * Determine if the user can create organizations.
     */
    public function create(User $user): bool
    {
        return true; // Any authenticated user can create an organization
    }

    /**
     * Determine if the user can update the organization.
     */
    public function update(User $user, Organization $organization): bool
    {
        return $user->hasAccessToOrganization($organization->id)
            && $user->canInOrganization('update-organization', $organization->id);
    }

    /**
     * Determine if the user can delete the organization.
     */
    public function delete(User $user, Organization $organization): bool
    {
        return $user->hasAccessToOrganization($organization->id)
            && $user->canInOrganization('delete-organization', $organization->id);
    }

    /**
     * Determine if the user can manage billing for the organization.
     */
    public function manageBilling(User $user, Organization $organization): bool
    {
        return $user->hasAccessToOrganization($organization->id)
            && $user->canInOrganization('manage-billing', $organization->id);
    }

    /**
     * Determine if the user can invite users to the organization.
     */
    public function inviteUsers(User $user, Organization $organization): bool
    {
        return $user->hasAccessToOrganization($organization->id)
            && $user->canInOrganization('invite-users', $organization->id);
    }
}
