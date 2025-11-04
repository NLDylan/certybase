<?php

namespace App\Http\Controllers\Campaigns;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Design;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CampaignController extends Controller
{
    /**
     * Display a listing of campaigns for the current organization.
     */
    public function index(Request $request): Response
    {
        $organizationId = $this->currentOrganizationIdOrFail();

        // Query campaigns scoped to organization from URL
        $campaigns = Campaign::query()
            ->where('organization_id', $organizationId)
            ->with(['design', 'creator', 'organization'])
            ->withCount('certificates')
            ->latest()
            ->paginate(15);

        return Inertia::render('Campaigns/Index', [
            'campaigns' => $campaigns,
        ]);
    }

    /**
     * Show the form for creating a new campaign.
     */
    public function create(): Response
    {
        $organizationId = $this->currentOrganizationIdOrFail();

        // Only show designs that belong to this organization
        $designs = Design::query()
            ->where('organization_id', $organizationId)
            ->where('status', 'active')
            ->get();

        return Inertia::render('Campaigns/Create', [
            'organizationId' => $organizationId,
            'designs' => $designs,
        ]);
    }

    /**
     * Store a newly created campaign.
     */
    public function store(Request $request)
    {
        $organizationId = $this->currentOrganizationIdOrFail();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'design_id' => ['required', 'uuid', 'exists:designs,id'],
            'variable_mapping' => ['nullable', 'array'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after:start_date'],
            'certificate_limit' => ['nullable', 'integer', 'min:1'],
        ]);

        // Verify the design belongs to this organization
        $design = Design::where('id', $validated['design_id'])
            ->where('organization_id', $organizationId)
            ->firstOrFail();

        $campaign = Campaign::create([
            'organization_id' => $organizationId,
            'creator_id' => $request->user()->id,
            ...$validated,
        ]);

        return redirect()->route('organizations.campaigns.show', [
            'organization_id' => $organizationId,
            'campaign' => $campaign->id,
        ]);
    }

    /**
     * Display the specified campaign.
     */
    public function show(Campaign $campaign): Response
    {
        $organizationId = $this->currentOrganizationIdOrFail();

        // Ensure campaign belongs to organization
        if ($campaign->organization_id !== $organizationId) {
            abort(404);
        }

        $campaign->load([
            'design',
            'creator',
            'organization',
            'certificates' => fn ($query) => $query->latest()->limit(50),
        ]);

        return Inertia::render('Campaigns/Show', [
            'campaign' => $campaign,
        ]);
    }

    /**
     * Update the specified campaign.
     */
    public function update(Request $request, Campaign $campaign)
    {
        $organizationId = $this->currentOrganizationIdOrFail();

        if ($campaign->organization_id !== $organizationId) {
            abort(404);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'variable_mapping' => ['nullable', 'array'],
            'status' => ['required', 'string'],
        ]);

        $campaign->update($validated);

        return redirect()->route('organizations.campaigns.show', [
            'organization_id' => $organizationId,
            'campaign' => $campaign->id,
        ]);
    }

    /**
     * Remove the specified campaign.
     */
    public function destroy(Campaign $campaign)
    {
        $organizationId = $this->currentOrganizationIdOrFail();

        if ($campaign->organization_id !== $organizationId) {
            abort(404);
        }

        $campaign->delete();

        return redirect()->route('organizations.campaigns.index', [
            'organization_id' => $organizationId,
        ]);
    }
}
