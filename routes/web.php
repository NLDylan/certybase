<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Temporary editor route without DB dependency
Route::get('editor/{id}', function (string $id) {
    return Inertia::render('editor/[id]', [
        'id' => $id,
    ]);
})->name('editor.show');

require __DIR__.'/settings.php';

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
    Route::post('/organizations/{organization}/switch', [\App\Http\Controllers\Organizations\OrganizationSwitchController::class, 'store'])
        ->name('organizations.switch');

    // Organization invitations (no org in URL needed)
    Route::get('/invitations/{token}', [\App\Http\Controllers\Organizations\OrganizationInvitationController::class, 'show'])
        ->name('organizations.invitations.show');
    Route::post('/invitations/{token}/accept', [\App\Http\Controllers\Organizations\OrganizationInvitationController::class, 'accept'])
        ->name('organizations.invitations.accept');
    Route::post('/invitations/{token}/decline', [\App\Http\Controllers\Organizations\OrganizationInvitationController::class, 'decline'])
        ->name('organizations.invitations.decline');
});

/*
|--------------------------------------------------------------------------
| Organization-Scoped Routes
|--------------------------------------------------------------------------
|
| These routes are scoped to an organization via URL parameter.
| Pattern: /organizations/{organization_id}/...
|
| The OrganizationContext middleware automatically:
| - Validates user has access to the organization
| - Sets Spatie permissions team ID
| - Shares organization data with Inertia
|
*/

Route::prefix('organizations/{organization_id}')->middleware(['auth', 'verified', 'organization'])->group(function () {
    // Dashboard
    Route::get('/dashboard', function () {
        return \Inertia\Inertia::render('Dashboard');
    })->name('organizations.dashboard');

    // Designs - organization-scoped
    Route::get('/designs', [\App\Http\Controllers\Designs\DesignController::class, 'index'])
        ->name('organizations.designs.index');
    Route::get('/designs/create', [\App\Http\Controllers\Designs\DesignController::class, 'create'])
        ->name('organizations.designs.create');
    Route::post('/designs', [\App\Http\Controllers\Designs\DesignController::class, 'store'])
        ->name('organizations.designs.store');
    Route::get('/designs/{design}', [\App\Http\Controllers\Designs\DesignController::class, 'show'])
        ->name('organizations.designs.show');
    Route::get('/designs/{design}/edit', [\App\Http\Controllers\Designs\DesignController::class, 'edit'])
        ->name('organizations.designs.edit');
    Route::put('/designs/{design}', [\App\Http\Controllers\Designs\DesignController::class, 'update'])
        ->name('organizations.designs.update');
    Route::delete('/designs/{design}', [\App\Http\Controllers\Designs\DesignController::class, 'destroy'])
        ->name('organizations.designs.destroy');

    // Campaigns - organization-scoped
    Route::resource('campaigns', \App\Http\Controllers\Campaigns\CampaignController::class)->names([
        'index' => 'organizations.campaigns.index',
        'create' => 'organizations.campaigns.create',
        'store' => 'organizations.campaigns.store',
        'show' => 'organizations.campaigns.show',
        'edit' => 'organizations.campaigns.edit',
        'update' => 'organizations.campaigns.update',
        'destroy' => 'organizations.campaigns.destroy',
    ]);

    // Certificates - organization-scoped
    Route::resource('certificates', \App\Http\Controllers\Certificates\CertificateController::class)->names([
        'index' => 'organizations.certificates.index',
        'create' => 'organizations.certificates.create',
        'store' => 'organizations.certificates.store',
        'show' => 'organizations.certificates.show',
        'edit' => 'organizations.certificates.edit',
        'update' => 'organizations.certificates.update',
        'destroy' => 'organizations.certificates.destroy',
    ]);

    // Organization settings
    Route::get('/settings', [\App\Http\Controllers\Organizations\OrganizationController::class, 'show'])
        ->name('organizations.settings');
    Route::put('/settings', [\App\Http\Controllers\Organizations\OrganizationController::class, 'update'])
        ->name('organizations.settings.update');

    // Organization users management
    Route::get('/users', [\App\Http\Controllers\Organizations\OrganizationUserController::class, 'index'])
        ->name('organizations.users.index');
    Route::post('/users/invite', [\App\Http\Controllers\Organizations\OrganizationUserController::class, 'invite'])
        ->name('organizations.users.invite');
    Route::delete('/users/{user}', [\App\Http\Controllers\Organizations\OrganizationUserController::class, 'destroy'])
        ->name('organizations.users.destroy');

    // Subscription/Billing
    Route::get('/subscription', [\App\Http\Controllers\Organizations\SubscriptionController::class, 'index'])
        ->name('organizations.subscription.index');
});
