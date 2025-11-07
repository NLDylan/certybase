<?php

namespace App\Policies;

use App\Models\Certificate;
use App\Models\User;

class CertificatePolicy
{
    /**
     * Determine if the user can view any certificates.
     */
    public function viewAny(User $user): bool
    {
        $organization = $user->currentOrganization();

        if (! $organization) {
            return false;
        }

        return $user->hasAccessToOrganization($organization->id)
            && $user->canInOrganization('view-certificates', $organization->id);
    }

    /**
     * Determine if the user can view the certificate.
     */
    public function view(User $user, Certificate $certificate): bool
    {
        return $user->hasAccessToOrganization($certificate->organization_id)
            && $user->canInOrganization('view-certificates', $certificate->organization_id);
    }

    /**
     * Determine if the user can create certificates.
     */
    public function create(User $user, ?string $organizationId = null): bool
    {
        if (! $organizationId) {
            return false;
        }

        return $user->hasAccessToOrganization($organizationId)
            && $user->canInOrganization('create-certificates', $organizationId);
    }

    /**
     * Determine if the user can update the certificate.
     */
    public function update(User $user, Certificate $certificate): bool
    {
        return $user->hasAccessToOrganization($certificate->organization_id)
            && $user->canInOrganization('update-certificates', $certificate->organization_id);
    }

    /**
     * Determine if the user can delete the certificate.
     */
    public function delete(User $user, Certificate $certificate): bool
    {
        return $user->hasAccessToOrganization($certificate->organization_id)
            && $user->canInOrganization('delete-certificates', $certificate->organization_id);
    }

    /**
     * Determine if the user can revoke the certificate.
     */
    public function revoke(User $user, Certificate $certificate): bool
    {
        return $user->hasAccessToOrganization($certificate->organization_id)
            && $user->canInOrganization('revoke-certificates', $certificate->organization_id);
    }

    /**
     * Determine if the user can download the certificate.
     */
    public function download(User $user, Certificate $certificate): bool
    {
        return $user->hasAccessToOrganization($certificate->organization_id)
            && $user->canInOrganization('download-certificates', $certificate->organization_id);
    }
}
