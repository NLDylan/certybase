<?php

namespace App\Http\Controllers\Designs;

use App\Http\Controllers\Controller;
use App\Models\Design;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DesignController extends Controller
{
    /**
     * Display a listing of designs for the current organization.
     */
    public function index(Request $request): Response
    {
        $organizationId = $this->currentOrganizationIdOrFail();

        // Query designs scoped to the organization from URL parameter
        $designs = Design::query()
            ->where('organization_id', $organizationId)
            ->with(['creator', 'organization'])
            ->latest()
            ->paginate(15);

        return Inertia::render('Designs/Index', [
            'designs' => $designs,
        ]);
    }

    /**
     * Show the form for creating a new design.
     */
    public function create(): Response
    {
        $organizationId = $this->currentOrganizationIdOrFail();

        return Inertia::render('Designs/Create', [
            'organizationId' => $organizationId,
        ]);
    }

    /**
     * Store a newly created design.
     */
    public function store(Request $request)
    {
        $organizationId = $this->currentOrganizationIdOrFail();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'design_data' => ['nullable', 'array'],
            'variables' => ['nullable', 'array'],
        ]);

        // Always scope to the organization from URL
        $design = Design::create([
            'organization_id' => $organizationId,
            'creator_id' => $request->user()->id,
            ...$validated,
        ]);

        return redirect()->route('organizations.designs.show', [
            'organization_id' => $organizationId,
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

        return Inertia::render('Designs/Show', [
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

        return Inertia::render('Designs/Edit', [
            'design' => $design,
        ]);
    }

    /**
     * Update the specified design.
     */
    public function update(Request $request, Design $design)
    {
        $organizationId = $this->currentOrganizationIdOrFail();

        // Ensure the design belongs to the organization
        if ($design->organization_id !== $organizationId) {
            abort(404);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'design_data' => ['nullable', 'array'],
            'variables' => ['nullable', 'array'],
            'status' => ['required', 'string'],
        ]);

        $design->update($validated);

        return redirect()->route('organizations.designs.show', [
            'organization_id' => $organizationId,
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

        return redirect()->route('organizations.designs.index', [
            'organization_id' => $organizationId,
        ]);
    }
}
