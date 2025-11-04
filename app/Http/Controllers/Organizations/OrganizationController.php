<?php

namespace App\Http\Controllers\Organizations;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationController extends Controller
{
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
}
