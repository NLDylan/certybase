<?php

namespace App\Http\Controllers\Organizations;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrganizationRequest;
use App\Http\Requests\UpdateOrganizationRequest;
use App\Services\OrganizationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationController extends Controller
{
    public function __construct(
        protected OrganizationService $organizationService
    ) {}

    /**
     * Display a listing of the user's organizations.
     */
    public function index(): Response
    {
        $user = Auth::user();

        $organizations = $user?->organizations()
            ->wherePivot('status', \App\Enums\OrganizationUserStatus::Active)
            ->get(['organizations.id', 'organizations.name', 'organizations.status']);

        return Inertia::render('organizations/Index', [
            'organizations' => $organizations,
        ]);
    }

    /**
     * Show the form for creating a new organization.
     */
    public function create(): Response
    {
        $this->authorize('create', \App\Models\Organization::class);

        return Inertia::render('organizations/Create');
    }

    /**
     * Store a newly created organization.
     */
    public function store(StoreOrganizationRequest $request): RedirectResponse
    {
        $user = Auth::user();

        $organization = $this->organizationService->create($request->validated(), $user->id);

        // Switch to the new organization
        $this->organizationService->switchOrganization($user->id, $organization->id);

        return redirect()->route('dashboard')
            ->with('success', 'Organization created successfully.');
    }

    /**
     * Display the organization settings page (uses session-based organization).
     */
    public function show(): Response
    {
        $organizationId = session('organization_id');

        if (! $organizationId) {
            abort(404, 'No organization selected');
        }

        $organization = \App\Models\Organization::findOrFail($organizationId);

        $this->authorize('view', $organization);

        return Inertia::render('organizations/Settings/General', [
            'organization' => $organization,
        ]);
    }

    /**
     * Update the organization (uses session-based organization).
     */
    public function update(UpdateOrganizationRequest $request): RedirectResponse
    {
        $organizationId = session('organization_id');

        if (! $organizationId) {
            abort(404, 'No organization selected');
        }

        $organization = \App\Models\Organization::findOrFail($organizationId);

        $this->authorize('update', $organization);

        $organization->update($request->validated());

        // Refresh the organization data
        $organization->refresh();

        return redirect()->route('organization.settings')
            ->with('success', 'Organization updated successfully.');
    }

    /**
     * Remove the specified organization.
     */
    public function destroy(string $organizationId): RedirectResponse
    {
        $organization = \App\Models\Organization::findOrFail($organizationId);

        $this->authorize('delete', $organization);

        $organization->delete();

        return redirect()->route('organizations.index')->with('success', 'Organization deleted successfully.');
    }
}
