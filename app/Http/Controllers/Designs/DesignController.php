<?php

namespace App\Http\Controllers\Designs;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDesignRequest;
use App\Http\Requests\UpdateDesignRequest;
use App\Models\Design;
use App\Services\DesignService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DesignController extends Controller
{
    public function __construct(
        protected DesignService $designService
    ) {
    }

    /**
     * Display a listing of designs for the current organization.
     */
    public function index(Request $request): Response
    {
        $organizationId = $this->currentOrganizationIdOrFail();

        $query = Design::query()
            ->where('organization_id', $organizationId)
            ->with(['creator', 'organization']);

        // Filter by status
        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        // Search by name
        if ($request->has('search') && $request->search !== '') {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');

        $allowedSortColumns = ['name', 'status', 'created_at', 'updated_at'];
        if (in_array($sortBy, $allowedSortColumns)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->latest();
        }

        $designs = $query->paginate(15)->withQueryString();

        return Inertia::render('designs/Index', [
            'designs' => $designs,
            'filters' => $request->only(['status', 'search', 'sort_by', 'sort_order']),
        ]);
    }

    /**
     * Show the form for creating a new design.
     */
    public function create(): Response
    {
        $organizationId = $this->currentOrganizationIdOrFail();

        return Inertia::render('designs/Create', [
            'organizationId' => $organizationId,
        ]);
    }

    /**
     * Store a newly created design.
     */
    public function store(StoreDesignRequest $request)
    {
        $organizationId = $this->currentOrganizationIdOrFail();
        $validated = $request->validated();

        $design = $this->designService->create(
            $organizationId,
            $request->user()->id,
            $validated
        );

        return redirect()->route('designs.show', [
            'design' => $design->id,
        ]);
    }

    /**
     * Display the specified design.
     */
    public function show(Request $request, Design $design): Response
    {
        $organizationId = $this->currentOrganizationIdOrFail();

        // Ensure the design belongs to the organization from URL
        // This prevents users from accessing other organizations' designs
        if ($design->organization_id !== $organizationId) {
            abort(404);
        }

        $design->load(['creator', 'organization', 'campaigns', 'certificates']);

        return Inertia::render('designs/Show', [
            'design' => $design,
        ]);
    }

    /**
     * Show the form for editing the specified design.
     */
    public function edit(Request $request, Design $design): Response
    {
        $organizationId = $this->currentOrganizationIdOrFail();

        // Ensure the design belongs to the organization
        if ($design->organization_id !== $organizationId) {
            abort(404);
        }

        return Inertia::render('designs/Edit', [
            'design' => $design,
        ]);
    }

    /**
     * Update the specified design.
     */
    public function update(UpdateDesignRequest $request, Design $design)
    {
        $organizationId = $this->currentOrganizationIdOrFail();

        // Ensure the design belongs to the organization
        if ($design->organization_id !== $organizationId) {
            abort(404);
        }

        $validated = $request->validated();
        $design->update($validated);

        return redirect()->route('designs.show', [
            'design' => $design->id,
        ]);
    }

    /**
     * Remove the specified design.
     */
    public function destroy(Design $design)
    {
        $organizationId = $this->currentOrganizationIdOrFail();

        // Ensure the design belongs to the organization
        if ($design->organization_id !== $organizationId) {
            abort(404);
        }

        $design->delete();

        return redirect()->route('designs.index');
    }
}
