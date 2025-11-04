<?php

namespace App\Http\Controllers\Organizations;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionController extends Controller
{
    /**
     * Display the organization subscription page.
     */
    public function index(): Response
    {
        $organizationId = session('organization_id');

        if (! $organizationId) {
            abort(404, 'No organization selected');
        }

        $organization = \App\Models\Organization::findOrFail($organizationId);

        $this->authorize('view', $organization);

        return Inertia::render('organizations/Subscription/Index', [
            'organization' => $organization,
        ]);
    }
}
