<?php

namespace App\Http\Controllers\Certificates;

use App\Enums\CampaignStatus;
use App\Enums\CertificateStatus;
use App\Enums\DesignStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCertificateRequest;
use App\Jobs\GenerateCertificatePDF;
use App\Models\Campaign;
use App\Models\Certificate;
use App\Models\Design;
use App\Services\CertificateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class CertificateController extends Controller
{
    public function __construct(
        protected CertificateService $certificateService
    ) {}

    /**
     * Display a listing of certificates for the current organization.
     */
    public function index(Request $request): Response
    {
        $organizationId = $this->currentOrganizationIdOrFail();

        $this->authorize('viewAny', Certificate::class);

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

        $campaigns = Campaign::query()
            ->select(['id', 'name'])
            ->where('organization_id', $organizationId)
            ->orderBy('name')
            ->get();

        $designs = Design::query()
            ->select(['id', 'name'])
            ->where('organization_id', $organizationId)
            ->where('status', DesignStatus::Active->value)
            ->orderBy('name')
            ->get();

        return Inertia::render('certificates/Index', [
            'certificates' => $certificates,
            'filters' => $request->only(['status', 'campaign_id', 'design_id', 'search', 'sort_by', 'sort_order']),
            'campaigns' => $campaigns,
            'designs' => $designs,
            'statuses' => collect(CertificateStatus::cases())->map(fn (CertificateStatus $status) => $status->value),
            'can' => [
                'create' => $request->user()?->can('create', [Certificate::class, $organizationId]) ?? false,
            ],
        ]);
    }

    /**
     * Show the form for creating a new certificate.
     */
    public function create(): Response
    {
        $organizationId = $this->currentOrganizationIdOrFail();

        $this->authorize('create', [Certificate::class, $organizationId]);

        $campaigns = Campaign::query()
            ->where('organization_id', $organizationId)
            ->where('status', CampaignStatus::Active->value)
            ->with('design')
            ->orderBy('name')
            ->get();

        return Inertia::render('certificates/Create', [
            'organizationId' => $organizationId,
            'campaigns' => $campaigns->map(fn (Campaign $campaign) => [
                'id' => $campaign->id,
                'name' => $campaign->name,
                'design' => [
                    'id' => $campaign->design?->id,
                    'name' => $campaign->design?->name,
                ],
            ]),
        ]);
    }

    /**
     * Store a newly created certificate.
     */
    public function store(StoreCertificateRequest $request): RedirectResponse
    {
        $organizationId = $this->currentOrganizationIdOrFail();
        $validated = $request->validated();

        $this->authorize('create', [Certificate::class, $organizationId]);

        $campaign = Campaign::query()
            ->whereKey($validated['campaign_id'])
            ->where('organization_id', $organizationId)
            ->firstOrFail();

        $certificate = $this->certificateService->create(
            $campaign->id,
            [
                'recipient_name' => $validated['recipient_name'],
                'recipient_email' => $validated['recipient_email'],
                'recipient_data' => $validated['recipient_data'] ?? [],
            ]
        );

        return Redirect::route('certificates.show', $certificate)
            ->with('flash', [
                'bannerStyle' => 'success',
                'banner' => 'Certificate created successfully.',
            ]);
    }

    /**
     * Display the specified certificate.
     */
    public function show(Request $request, Certificate $certificate): Response
    {
        $organizationId = $this->currentOrganizationIdOrFail();

        // Ensure certificate belongs to organization
        if ($certificate->organization_id !== $organizationId) {
            abort(404);
        }

        $this->authorize('view', $certificate);

        $certificate->load(['design', 'campaign', 'organization', 'issuedToUser']);

        $pdfMedia = $certificate->getFirstMedia('certificate_pdf');

        return Inertia::render('certificates/Show', [
            'certificate' => array_merge($certificate->toArray(), [
                'has_pdf' => (bool) $pdfMedia,
                'pdf_generated_at' => $pdfMedia?->getCustomProperty('generated_at'),
            ]),
            'can' => [
                'download' => $request->user()?->can('download', $certificate) ?? false,
                'revoke' => $request->user()?->can('revoke', $certificate) ?? false,
            ],
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

        $this->authorize('download', $certificate);

        $media = $certificate->getFirstMedia('certificate_pdf');

        if (! $media) {
            GenerateCertificatePDF::dispatch($certificate->id);

            abort(409, 'Certificate PDF is still generating.');
        }

        return $media->toInlineResponse(request());
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

        $this->authorize('revoke', $certificate);

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $this->certificateService->revoke($certificate->id, $validated['reason'] ?? null);

        return Redirect::route('certificates.show', $certificate)
            ->with('flash', [
                'bannerStyle' => 'success',
                'banner' => 'Certificate revoked successfully.',
            ]);
    }
}
