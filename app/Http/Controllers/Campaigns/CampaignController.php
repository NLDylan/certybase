<?php

namespace App\Http\Controllers\Campaigns;

use App\Enums\CampaignStatus;
use App\Enums\DesignStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCampaignRequest;
use App\Http\Requests\UpdateCampaignRequest;
use App\Models\Campaign;
use App\Models\Design;
use App\Services\CampaignService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use RuntimeException;
use Inertia\Inertia;
use Inertia\Response;

class CampaignController extends Controller
{
    public function __construct(
        protected CampaignService $campaignService
    ) {}

    /**
     * Display a listing of campaigns for the current organization.
     */
    public function index(Request $request): Response
    {
        $organizationId = $this->currentOrganizationIdOrFail();

        $this->authorize('viewAny', Campaign::class);

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

        $campaigns = $query
            ->paginate(15)
            ->through(function (Campaign $campaign) use ($request) {
                return array_merge($campaign->toArray(), [
                    'can' => [
                        'view' => $request->user()?->can('view', $campaign) ?? false,
                        'update' => $request->user()?->can('update', $campaign) ?? false,
                        'delete' => $request->user()?->can('delete', $campaign) ?? false,
                    ],
                ]);
            })
            ->withQueryString();

        $designs = Design::query()
            ->where('organization_id', $organizationId)
            ->where('status', DesignStatus::Active->value)
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get();

        return Inertia::render('campaigns/Index', [
            'campaigns' => $campaigns,
            'filters' => $request->only(['status', 'design_id', 'search', 'sort_by', 'sort_order']),
            'designs' => $designs,
            'statuses' => collect(CampaignStatus::cases())->map(fn (CampaignStatus $status) => $status->value),
            'can' => [
                'create' => $request->user()?->can('create', [Campaign::class, $organizationId]) ?? false,
            ],
        ]);
    }

    /**
     * Show the form for creating a new campaign.
     */
    public function create(): Response
    {
        $organizationId = $this->currentOrganizationIdOrFail();

        $this->authorize('create', [Campaign::class, $organizationId]);

        // Only show designs that belong to this organization
        $designs = Design::query()
            ->where('organization_id', $organizationId)
            ->where('status', DesignStatus::Active->value)
            ->get(['id', 'name', 'variables']);

        return Inertia::render('campaigns/Create', [
            'organizationId' => $organizationId,
            'designs' => $designs->map(fn (Design $design) => [
                'id' => $design->id,
                'name' => $design->name,
                'variables' => array_keys($design->variables ?? []),
            ]),
            'defaultVariableMapping' => [
                'recipient_name' => 'name',
                'recipient_email' => 'email',
                'variables' => [],
            ],
        ]);
    }

    /**
     * Store a newly created campaign.
     */
    public function store(StoreCampaignRequest $request)
    {
        $organizationId = $this->currentOrganizationIdOrFail();
        $validated = $request->validated();
        $this->authorize('create', [Campaign::class, $organizationId]);

        Design::query()
            ->whereKey($validated['design_id'])
            ->where('organization_id', $organizationId)
            ->firstOrFail();

        $campaign = $this->campaignService->create(
            $organizationId,
            $request->user()->id,
            $validated
        );

        return Redirect::route('campaigns.show', $campaign)
            ->with('flash', [
                'bannerStyle' => 'success',
                'banner' => 'Campaign created successfully.',
            ]);
    }

    /**
     * Display the specified campaign.
     */
    public function show(Request $request, Campaign $campaign): Response
    {
        $organizationId = $this->currentOrganizationIdOrFail();

        // Ensure campaign belongs to organization
        if ($campaign->organization_id !== $organizationId) {
            abort(404);
        }

        $this->authorize('view', $campaign);

        $campaign->load([
            'design',
            'creator',
            'organization',
            'certificates' => fn ($query) => $query->latest()->limit(50),
        ]);

        return Inertia::render('campaigns/Show', [
            'campaign' => $campaign,
            'can' => [
                'update' => $request->user()?->can('update', $campaign) ?? false,
                'execute' => $request->user()?->can('execute', $campaign) ?? false,
                'delete' => $request->user()?->can('delete', $campaign) ?? false,
            ],
        ]);
    }

    /**
     * Show the edit form for the campaign.
     */
    public function edit(Request $request, Campaign $campaign): Response
    {
        $organizationId = $this->currentOrganizationIdOrFail();

        if ($campaign->organization_id !== $organizationId) {
            abort(404);
        }

        $this->authorize('update', $campaign);

        $designs = Design::query()
            ->where('organization_id', $organizationId)
            ->where('status', DesignStatus::Active->value)
            ->select(['id', 'name', 'variables'])
            ->orderBy('name')
            ->get();

        return Inertia::render('campaigns/Edit', [
            'campaign' => $campaign->load('design'),
            'designs' => $designs->map(fn (Design $design) => [
                'id' => $design->id,
                'name' => $design->name,
                'variables' => array_keys($design->variables ?? []),
            ]),
            'statuses' => collect(CampaignStatus::cases())->map(fn (CampaignStatus $status) => $status->value),
            'can' => [
                'update' => $request->user()?->can('update', $campaign) ?? false,
                'execute' => $request->user()?->can('execute', $campaign) ?? false,
                'delete' => $request->user()?->can('delete', $campaign) ?? false,
            ],
        ]);
    }

    /**
     * Update the specified campaign.
     */
    public function update(UpdateCampaignRequest $request, Campaign $campaign): RedirectResponse
    {
        $organizationId = $this->currentOrganizationIdOrFail();

        if ($campaign->organization_id !== $organizationId) {
            abort(404);
        }

        $validated = $request->validated();
        $this->authorize('update', $campaign);
        $campaign->update($validated);

        return Redirect::route('campaigns.show', $campaign)
            ->with('flash', [
                'bannerStyle' => 'success',
                'banner' => 'Campaign updated successfully.',
            ]);
    }

    /**
     * Remove the specified campaign.
     */
    public function destroy(Campaign $campaign): RedirectResponse
    {
        $organizationId = $this->currentOrganizationIdOrFail();

        if ($campaign->organization_id !== $organizationId) {
            abort(404);
        }

        $this->authorize('delete', $campaign);

        $campaign->delete();

        return Redirect::route('campaigns.index')
            ->with('flash', [
                'bannerStyle' => 'success',
                'banner' => 'Campaign deleted.',
            ]);
    }

    /**
     * Execute the specified campaign.
     */
    public function execute(Campaign $campaign): RedirectResponse
    {
        $organizationId = $this->currentOrganizationIdOrFail();

        if ($campaign->organization_id !== $organizationId) {
            abort(404);
        }

        $this->authorize('execute', $campaign);

        $updatedCampaign = $this->campaignService->execute($campaign->id);

        return Redirect::route('campaigns.show', $updatedCampaign)
            ->with('flash', [
                'bannerStyle' => 'success',
                'banner' => 'Campaign execution started.',
            ]);
    }

    /**
     * Finish the specified campaign manually.
     */
    public function finish(Request $request, Campaign $campaign): RedirectResponse
    {
        $organizationId = $this->currentOrganizationIdOrFail();

        if ($campaign->organization_id !== $organizationId) {
            abort(404);
        }

        $this->authorize('execute', $campaign);

        try {
            $finishedCampaign = $this->campaignService->finish($campaign->id);
        } catch (RuntimeException $exception) {
            return Redirect::route('campaigns.show', $campaign)
                ->with('flash', [
                    'bannerStyle' => 'error',
                    'banner' => $exception->getMessage(),
                ]);
        }

        return Redirect::route('campaigns.show', $finishedCampaign)
            ->with('flash', [
                'bannerStyle' => 'success',
                'banner' => 'Campaign marked as completed.',
            ]);
    }
}
