<?php

namespace App\Http\Controllers\Organizations;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Services\OrganizationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class OrganizationSwitchController extends Controller
{
    public function __construct(
        protected OrganizationService $organizationService
    ) {}

    /**
     * Switch to the specified organization.
     */
    public function store(Organization $organization): RedirectResponse
    {
        $user = Auth::user();

        // Verify user has access
        if (! $user->hasAccessToOrganization($organization->id)) {
            abort(403, 'You do not have access to this organization.');
        }

        $this->organizationService->switchOrganization($user->id, $organization->id);

        return redirect()->route('organizations.dashboard', [
            'organization_id' => $organization->id,
        ])->with('success', 'Switched organization successfully.');
    }
}
