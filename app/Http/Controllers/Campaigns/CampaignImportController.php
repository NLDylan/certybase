<?php

namespace App\Http\Controllers\Campaigns;

use App\Http\Controllers\Controller;
use App\Http\Requests\ImportCertificatesRequest;
use App\Models\Campaign;
use App\Services\CampaignService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class CampaignImportController extends Controller
{
    public function __construct(protected CampaignService $campaignService) {}

    public function create(Campaign $campaign): Response
    {
        $organizationId = $this->currentOrganizationIdOrFail();

        if ($campaign->organization_id !== $organizationId) {
            abort(404);
        }

        $this->authorize('execute', $campaign);

        return Inertia::render('campaigns/Import', [
            'campaign' => [
                'id' => $campaign->id,
                'name' => $campaign->name,
                'status' => $campaign->status,
                'certificates_issued' => $campaign->certificates_issued,
                'certificate_limit' => $campaign->certificate_limit,
            ],
        ]);
    }

    public function store(ImportCertificatesRequest $request, Campaign $campaign): RedirectResponse
    {
        $organizationId = $this->currentOrganizationIdOrFail();

        if ($campaign->organization_id !== $organizationId) {
            abort(404);
        }

        $this->authorize('execute', $campaign);

        $rowCount = $this->campaignService->importRecipients($campaign->id, $request->file('file'));

        return Redirect::route('campaigns.show', $campaign)
            ->with('flash', [
                'bannerStyle' => 'success',
                'banner' => $rowCount === 0
                    ? 'Import queued. No recipients detected in the file.'
                    : sprintf('%d recipient%s queued for import.', $rowCount, $rowCount === 1 ? '' : 's'),
            ]);
    }
}
