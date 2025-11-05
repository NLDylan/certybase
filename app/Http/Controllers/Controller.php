<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

abstract class Controller
{
    use AuthorizesRequests;

    /**
     * Get the current organization from session.
     */
    protected function currentOrganization(): ?\App\Models\Organization
    {
        $organizationId = Session::get('organization_id');

        if (! $organizationId) {
            return null;
        }

        $user = Auth::user();
        if (! $user) {
            return null;
        }

        return $user->organizations()
            ->where('organizations.id', $organizationId)
            ->first();
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
     * Get the current organization ID from session.
     */
    protected function currentOrganizationId(): ?string
    {
        return Session::get('organization_id');
    }

    /**
     * Get the current organization ID or abort.
     */
    protected function currentOrganizationIdOrFail(): string
    {
        $organizationId = $this->currentOrganizationId();

        if (! $organizationId) {
            abort(404, 'Organization ID not found in session');
        }

        return $organizationId;
    }
}
