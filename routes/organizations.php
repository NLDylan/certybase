<?php

use Illuminate\Support\Facades\Route;

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
