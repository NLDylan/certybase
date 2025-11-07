<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

// Dashboard route moved to organization-scoped routes below

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

    // Switch organization (redirects to dashboard)
    Route::post('/organizations/{organization}/switch', [\App\Http\Controllers\Organizations\OrganizationSwitchController::class, 'store'])
        ->name('organizations.switch');

    // Organization invitations (no org in URL needed)
    Route::get('/invitations/{token}', [\App\Http\Controllers\Organizations\OrganizationInvitationController::class, 'show'])
        ->name('organizations.invitations.show');
    Route::post('/invitations/{token}/accept', [\App\Http\Controllers\Organizations\OrganizationInvitationController::class, 'accept'])
        ->name('organizations.invitations.accept');
    Route::post('/invitations/{token}/decline', [\App\Http\Controllers\Organizations\OrganizationInvitationController::class, 'decline'])
        ->name('organizations.invitations.decline');

    // Organization settings (session-based, no UUID in URL)
    Route::get('/organization/settings', [\App\Http\Controllers\Organizations\OrganizationController::class, 'show'])
        ->middleware('organization')
        ->name('organization.settings');
    Route::get('/organization/settings/branding', [\App\Http\Controllers\Organizations\OrganizationController::class, 'branding'])
        ->middleware('organization')
        ->name('organization.settings.branding');
    Route::put('/organization/settings', [\App\Http\Controllers\Organizations\OrganizationController::class, 'update'])
        ->middleware('organization')
        ->name('organization.settings.update');

    // Organization users (session-based, no UUID in URL)
    Route::get('/organization/users', [\App\Http\Controllers\Organizations\OrganizationUserController::class, 'index'])
        ->middleware('organization')
        ->name('organization.users.index');
    Route::post('/organization/users/invite', [\App\Http\Controllers\Organizations\OrganizationUserController::class, 'invite'])
        ->middleware('organization')
        ->name('organization.users.invite');
    Route::delete('/organization/users/{user}', [\App\Http\Controllers\Organizations\OrganizationUserController::class, 'destroy'])
        ->middleware('organization')
        ->name('organization.users.destroy');

    // Organization subscription (session-based, no UUID in URL)
    Route::get('/organization/subscription', [\App\Http\Controllers\Organizations\SubscriptionController::class, 'index'])
        ->middleware('organization')
        ->name('organization.subscription.index');
    Route::post('/organization/subscription/checkout/{priceId}', [\App\Http\Controllers\Organizations\SubscriptionController::class, 'checkout'])
        ->middleware('organization')
        ->name('organization.subscription.checkout');
    Route::get('/organization/subscription/portal', [\App\Http\Controllers\Organizations\SubscriptionController::class, 'portal'])
        ->middleware('organization')
        ->name('organization.subscription.portal');
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

Route::middleware(['auth', 'verified', 'organization'])->group(function () {
    // Dashboard
    Route::get('/dashboard', function () {
        return \Inertia\Inertia::render('Dashboard');
    })->name('dashboard');

    // Designs - organization-scoped
    Route::get('/designs', [\App\Http\Controllers\Designs\DesignController::class, 'index'])
        ->name('designs.index');
    Route::get('/designs/create', [\App\Http\Controllers\Designs\DesignController::class, 'create'])
        ->name('designs.create');
    Route::post('/designs', [\App\Http\Controllers\Designs\DesignController::class, 'store'])
        ->name('designs.store');
    Route::get('/designs/{design}', [\App\Http\Controllers\Designs\DesignController::class, 'show'])
        ->name('designs.show');
    Route::get('/designs/{design}/details', [\App\Http\Controllers\Designs\DesignController::class, 'editDetails'])
        ->name('designs.details.edit');
    Route::get('/designs/{design}/edit', [\App\Http\Controllers\Designs\DesignController::class, 'edit'])
        ->name('designs.edit');
    Route::get('/editor/{design}', [\App\Http\Controllers\Designs\DesignController::class, 'edit'])
        ->name('editor.show');
    Route::put('/designs/{design}', [\App\Http\Controllers\Designs\DesignController::class, 'update'])
        ->name('designs.update');
    Route::delete('/designs/{design}', [\App\Http\Controllers\Designs\DesignController::class, 'destroy'])
        ->name('designs.destroy');

    // Design image uploads
    Route::post('/designs/{design}/images/upload', [\App\Http\Controllers\Designs\DesignImageController::class, 'upload'])
        ->name('designs.images.upload');
    Route::post('/designs/{design}/images/download', [\App\Http\Controllers\Designs\DesignImageController::class, 'downloadFromUrl'])
        ->name('designs.images.download');
    Route::get('/designs/{design}/images/{media}', [\App\Http\Controllers\Designs\DesignImageController::class, 'show'])
        ->name('designs.images.show');

    // Reusable media endpoints
    Route::get('/media/{media}', [\App\Http\Controllers\Media\MediaController::class, 'show'])
        ->name('media.show');
    Route::post('/media', [\App\Http\Controllers\Media\MediaController::class, 'store'])
        ->name('media.store');
    Route::post('/media/from-url', [\App\Http\Controllers\Media\MediaController::class, 'storeFromUrl'])
        ->middleware('throttle:media-remote')
        ->name('media.from-url');
    Route::delete('/media', [\App\Http\Controllers\Media\MediaController::class, 'destroy'])
        ->name('media.destroy');

    // Campaigns - organization-scoped
    Route::resource('campaigns', \App\Http\Controllers\Campaigns\CampaignController::class)->names([
        'index' => 'campaigns.index',
        'create' => 'campaigns.create',
        'store' => 'campaigns.store',
        'show' => 'campaigns.show',
        'edit' => 'campaigns.edit',
        'update' => 'campaigns.update',
        'destroy' => 'campaigns.destroy',
    ]);

    // Certificates - organization-scoped
    Route::resource('certificates', \App\Http\Controllers\Certificates\CertificateController::class)->names([
        'index' => 'certificates.index',
        'create' => 'certificates.create',
        'store' => 'certificates.store',
        'show' => 'certificates.show',
        'edit' => 'certificates.edit',
        'update' => 'certificates.update',
        'destroy' => 'certificates.destroy',
    ]);
    Route::get('/certificates/{certificate}/download', [\App\Http\Controllers\Certificates\CertificateController::class, 'download'])
        ->name('certificates.download');
    Route::post('/certificates/{certificate}/revoke', [\App\Http\Controllers\Certificates\CertificateController::class, 'revoke'])
        ->name('certificates.revoke');
});
