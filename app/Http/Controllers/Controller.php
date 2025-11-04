<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller
{
    use AuthorizesRequests;

    /**
     * Get the current organization from the route parameter.
     * This works because OrganizationContext middleware validates access.
     */
    protected function currentOrganization(): ?\App\Models\Organization
    {
        $organizationId = request()->route('organization_id') ?? request()->route('organization');

        if (! $organizationId) {
            return null;
        }

        return \App\Models\Organization::find($organizationId);
    }

    /**
     * Get the current organization or abort with 404.
     */
    protected function currentOrganizationOrFail(): \App\Models\Organization
    {
        $organization = $this->currentOrganization();

        if (! $organization) {
            abort(404, 'Organization not found');
        }

        return $organization;
    }

    /**
     * Get the current organization ID from route.
     */
    protected function currentOrganizationId(): ?string
    {
        return request()->route('organization_id') ?? request()->route('organization');
    }

    /**
     * Get the current organization ID or abort.
     */
    protected function currentOrganizationIdOrFail(): string
    {
        $organizationId = $this->currentOrganizationId();

        if (! $organizationId) {
            abort(404, 'Organization ID not found in route');
        }

        return $organizationId;
    }
}
