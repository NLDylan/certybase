<?php

namespace App\Policies;

use App\Models\Campaign;
use App\Models\User;

class CampaignPolicy
{
    /**
     * Determine if the user can view any campaigns.
     */
    public function viewAny(User $user): bool
    {
        $organizationId = session('organization_id');

        if (! $organizationId) {
            return false;
        }

        return $user->hasAccessToOrganization($organizationId)
            && $user->canInOrganization('view-campaigns', $organizationId);
    }

    /**
     * Determine if the user can view the campaign.
     */
    public function view(User $user, Campaign $campaign): bool
    {
        return $user->hasAccessToOrganization($campaign->organization_id)
            && $user->canInOrganization('view-campaigns', $campaign->organization_id);
    }

    /**
     * Determine if the user can create campaigns.
     */
    public function create(User $user, ?string $organizationId = null): bool
    {
        if (! $organizationId) {
            return false;
        }

        return $user->hasAccessToOrganization($organizationId)
            && $user->canInOrganization('create-campaigns', $organizationId);
    }

    /**
     * Determine if the user can update the campaign.
     */
    public function update(User $user, Campaign $campaign): bool
    {
        return $user->hasAccessToOrganization($campaign->organization_id)
            && $user->canInOrganization('update-campaigns', $campaign->organization_id);
    }

    /**
     * Determine if the user can delete the campaign.
     */
    public function delete(User $user, Campaign $campaign): bool
    {
        return $user->hasAccessToOrganization($campaign->organization_id)
            && $user->canInOrganization('delete-campaigns', $campaign->organization_id);
    }

    /**
     * Determine if the user can execute the campaign.
     */
    public function execute(User $user, Campaign $campaign): bool
    {
        return $user->hasAccessToOrganization($campaign->organization_id)
            && $user->canInOrganization('execute-campaigns', $campaign->organization_id);
    }
}
