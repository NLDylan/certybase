<?php

namespace App\Http\Controllers\Campaigns;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCampaignRequest;
use App\Http\Requests\UpdateCampaignRequest;
use App\Models\Campaign;
use App\Models\Design;
use App\Services\CampaignService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CampaignController extends Controller
{
    public function __construct(
        protected CampaignService $campaignService
    ) {
    }

    /**
     * Display a listing of campaigns for the current organization.
     */
    public function index(Request $request): Response
    {
        $organizationId = $this->currentOrganizationIdOrFail();

        $query = Campaign::query()
            ->where('organization_id', $organizationId)
            ->with(['design', 'creator', 'organization'])
            ->withCount('certificates');

        // Filter by status
        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        // Filter by design
        if ($request->has('design_id') && $request->design_id !== '') {
            $query->where('design_id', $request->design_id);
        }

        // Search by name
        if ($request->has('search') && $request->search !== '') {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');

        $allowedSortColumns = ['name', 'status', 'start_date', 'end_date', 'certificates_issued', 'created_at'];
        if (in_array($sortBy, $allowedSortColumns)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->latest();
        }

        $campaigns = $query->paginate(15)->withQueryString();

        return Inertia::render('campaigns/Index', [
            'campaigns' => $campaigns,
            'filters' => $request->only(['status', 'design_id', 'search', 'sort_by', 'sort_order']),
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

        return Inertia::render('campaigns/Create', [
            'organizationId' => $organizationId,
            'designs' => $designs,
        ]);
    }

    /**
     * Store a newly created campaign.
     */
    public function store(StoreCampaignRequest $request)
    {
        $organizationId = $this->currentOrganizationIdOrFail();
        $validated = $request->validated();

        // Verify the design belongs to this organization
        $design = Design::where('id', $validated['design_id'])
            ->where('organization_id', $organizationId)
            ->firstOrFail();

        $campaign = $this->campaignService->create(
            $organizationId,
            $request->user()->id,
            $validated
        );

        return redirect()->route('campaigns.show', [
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

        return Inertia::render('campaigns/Show', [
            'campaign' => $campaign,
        ]);
    }

    /**
     * Update the specified campaign.
     */
    public function update(UpdateCampaignRequest $request, Campaign $campaign)
    {
        $organizationId = $this->currentOrganizationIdOrFail();

        if ($campaign->organization_id !== $organizationId) {
            abort(404);
        }

        $validated = $request->validated();
        $campaign->update($validated);

        return redirect()->route('campaigns.show', [
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

        return redirect()->route('campaigns.index');
    }
}
