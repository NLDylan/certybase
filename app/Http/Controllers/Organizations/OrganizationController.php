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

        return redirect()->route('organizations.dashboard', [
            'organization_id' => $organization->id,
        ])->with('success', 'Organization created successfully.');
    }

    /**
     * Display the specified organization.
     */
    public function show(string $organizationId): Response
    {
        $organization = \App\Models\Organization::findOrFail($organizationId);

        $this->authorize('view', $organization);

        $organization->load([
            'users' => function ($query) {
                $query->wherePivot('status', \App\Enums\OrganizationUserStatus::Active);
            },
            'designs' => function ($query) {
                $query->latest()->limit(5);
            },
            'campaigns' => function ($query) {
                $query->latest()->limit(5);
            },
            'certificates' => function ($query) {
                $query->latest()->limit(5);
            },
        ]);

        return Inertia::render('organizations/Show', [
            'organization' => $organization,
        ]);
    }

    /**
     * Update the specified organization.
     */
    public function update(UpdateOrganizationRequest $request, string $organizationId): RedirectResponse
    {
        $organization = \App\Models\Organization::findOrFail($organizationId);

        $this->authorize('update', $organization);

        $organization->update($request->validated());

        return redirect()->back()->with('success', 'Organization updated successfully.');
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
