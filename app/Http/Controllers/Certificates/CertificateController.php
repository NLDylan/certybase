<?php

namespace App\Http\Controllers\Certificates;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCertificateRequest;
use App\Models\Certificate;
use App\Services\CertificateService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CertificateController extends Controller
{
    public function __construct(
        protected CertificateService $certificateService
    ) {
    }

    /**
     * Display a listing of certificates for the current organization.
     */
    public function index(Request $request): Response
    {
        $organizationId = $this->currentOrganizationIdOrFail();

        $query = Certificate::query()
            ->where('organization_id', $organizationId)
            ->with(['design', 'campaign', 'organization']);

        // Filter by status
        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        // Filter by campaign
        if ($request->has('campaign_id') && $request->campaign_id !== '') {
            $query->where('campaign_id', $request->campaign_id);
        }

        // Filter by design
        if ($request->has('design_id') && $request->design_id !== '') {
            $query->where('design_id', $request->design_id);
        }

        // Search by recipient name or email
        if ($request->has('search') && $request->search !== '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('recipient_name', 'like', "%{$search}%")
                    ->orWhere('recipient_email', 'like', "%{$search}%");
            });
        }

        // Sort
        $sortBy = $request->get('sort_by', 'issued_at');
        $sortOrder = $request->get('sort_order', 'desc');

        $allowedSortColumns = ['recipient_name', 'recipient_email', 'status', 'issued_at', 'created_at'];
        if (in_array($sortBy, $allowedSortColumns)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->latest('issued_at');
        }

        $certificates = $query->paginate(15)->withQueryString();

        return Inertia::render('certificates/Index', [
            'certificates' => $certificates,
            'filters' => $request->only(['status', 'campaign_id', 'design_id', 'search', 'sort_by', 'sort_order']),
        ]);
    }

    /**
     * Show the form for creating a new certificate.
     */
    public function create(): Response
    {
        $organizationId = $this->currentOrganizationIdOrFail();

        // Get active campaigns for this organization
        $campaigns = \App\Models\Campaign::query()
            ->where('organization_id', $organizationId)
            ->where('status', 'active')
            ->with('design')
            ->get();

        return Inertia::render('certificates/Create', [
            'organizationId' => $organizationId,
            'campaigns' => $campaigns,
        ]);
    }

    /**
     * Store a newly created certificate.
     */
    public function store(StoreCertificateRequest $request)
    {
        $organizationId = $this->currentOrganizationIdOrFail();
        $validated = $request->validated();

        $certificate = $this->certificateService->create(
            $validated['campaign_id'],
            [
                'recipient_name' => $validated['recipient_name'],
                'recipient_email' => $validated['recipient_email'],
                'recipient_data' => $validated['recipient_data'] ?? [],
            ]
        );

        return redirect()->route('certificates.show', [
            'certificate' => $certificate->id,
        ]);
    }

    /**
     * Display the specified certificate.
     */
    public function show(Certificate $certificate): Response
    {
        $organizationId = $this->currentOrganizationIdOrFail();

        // Ensure certificate belongs to organization
        if ($certificate->organization_id !== $organizationId) {
            abort(404);
        }

        $certificate->load(['design', 'campaign', 'organization', 'issuedToUser']);

        return Inertia::render('certificates/Show', [
            'certificate' => $certificate,
        ]);
    }

    /**
     * Download the certificate PDF.
     */
    public function download(Certificate $certificate)
    {
        $organizationId = $this->currentOrganizationIdOrFail();

        if ($certificate->organization_id !== $organizationId) {
            abort(404);
        }

        $media = $certificate->getFirstMedia('certificate_pdf');

        if (! $media) {
            abort(404, 'Certificate PDF not found');
        }

        return $media;
    }

    /**
     * Revoke a certificate.
     */
    public function revoke(Request $request, Certificate $certificate)
    {
        $organizationId = $this->currentOrganizationIdOrFail();

        if ($certificate->organization_id !== $organizationId) {
            abort(404);
        }

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $this->certificateService->revoke($certificate->id, $validated['reason'] ?? null);

        return redirect()->route('certificates.show', [
            'certificate' => $certificate->id,
        ]);
    }
}

