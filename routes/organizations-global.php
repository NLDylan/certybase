<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Global Organization Routes (Not Scoped)
|--------------------------------------------------------------------------
|
| These routes don't require organization_id in URL:
| - Organization listing (user's organizations)
| - Creating new organizations
| - Switching organizations
| - Accepting invitations
|
*/

Route::middleware(['auth', 'verified'])->group(function () {
    // Organization listing (user's organizations)
    Route::get('/organizations', [\App\Http\Controllers\Organizations\OrganizationController::class, 'index'])
        ->name('organizations.index');

    // Create organization
    Route::get('/organizations/create', [\App\Http\Controllers\Organizations\OrganizationController::class, 'create'])
        ->name('organizations.create');
    Route::post('/organizations', [\App\Http\Controllers\Organizations\OrganizationController::class, 'store'])
        ->name('organizations.store');

    // Switch organization (redirects to organization-scoped dashboard)
    Route::post('/organizations/{organization}/switch', function (\App\Models\Organization $organization) {
        \Illuminate\Support\Facades\Auth::user()->switchOrganization($organization->id);
        return redirect()->route('dashboard');
    })->name('organizations.switch');

    // Organization invitations (no org in URL needed)
    Route::get('/invitations/{token}', [\App\Http\Controllers\Organizations\OrganizationInvitationController::class, 'show'])
        ->name('organizations.invitations.show');
    Route::post('/invitations/{token}/accept', [\App\Http\Controllers\Organizations\OrganizationInvitationController::class, 'accept'])
        ->name('organizations.invitations.accept');
    Route::post('/invitations/{token}/decline', [\App\Http\Controllers\Organizations\OrganizationInvitationController::class, 'decline'])
        ->name('organizations.invitations.decline');
});
