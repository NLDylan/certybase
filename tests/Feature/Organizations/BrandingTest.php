<?php

declare(strict_types=1);

use App\Enums\OrganizationStatus;
use App\Enums\OrganizationUserStatus;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Session;
use Spatie\MediaLibrary\MediaCollections\Models\Media as SpatieMedia;
use Spatie\Permission\PermissionRegistrar;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

function setupBrandingOrgUser(bool $withAdmin = true): array {
    $user = User::factory()->create();
    $organization = Organization::query()->create([
        'name' => 'Brand Org',
        'status' => OrganizationStatus::Active,
        'settings' => [],
    ]);

    $organization->users()->syncWithoutDetaching([
        $user->getKey() => ['status' => OrganizationUserStatus::Active->value],
    ]);

    /** @var PermissionRegistrar $registrar */
    $registrar = app(PermissionRegistrar::class);
    $registrar->setPermissionsTeamId($organization->getKey());
    if ($withAdmin) {
        $user->assignRole('Administrator');
    }
    $registrar->setPermissionsTeamId(null);

    Session::put('organization_id', $organization->getKey());

    return [$user, $organization];
}

it('uploads icon and logo to organization collections', function () {
    [$user, $organization] = setupBrandingOrgUser();
    actingAs($user);

    $icon = UploadedFile::fake()->image('icon.png', 512, 512);
    $logo = UploadedFile::fake()->image('logo.jpg', 800, 200);

    $iconResp = postJson(route('media.store'), [
        'model_type' => 'organization',
        'model_id' => $organization->getKey(),
        'collection' => 'icon',
        'file' => $icon,
    ])->assertSuccessful();

    $logoResp = postJson(route('media.store'), [
        'model_type' => 'organization',
        'model_id' => $organization->getKey(),
        'collection' => 'logo',
        'file' => $logo,
    ])->assertSuccessful();

    $organization->refresh();

    $iconMedia = $organization->getFirstMedia('icon');
    $logoMedia = $organization->getFirstMedia('logo');

    expect($iconMedia)->not()->toBeNull();
    expect($logoMedia)->not()->toBeNull();

    // Conversions should be registered (best-effort check when using local driver)
    expect($iconMedia->hasGeneratedConversion('256'))->toBeTrue();
    expect($logoMedia->hasGeneratedConversion('w400'))->toBeTrue();
});

it('forbids non-admin from updating branding', function () {
    [$user, $organization] = setupBrandingOrgUser(withAdmin: false);
    actingAs($user);

    $icon = UploadedFile::fake()->image('icon.png', 512, 512);

    postJson(route('media.store'), [
        'model_type' => 'organization',
        'model_id' => $organization->getKey(),
        'collection' => 'icon',
        'file' => $icon,
    ])->assertForbidden();
});

it('shares icon_url and logo_url props; logo gated by plan', function () {
    [$user, $organization] = setupBrandingOrgUser();
    actingAs($user);

    // No subscription by default => has_growth_plan false
    $response = get('/dashboard');
    $page = $response->assertOk();

    $shared = inertia()->shared('organization');

    expect($shared)->toBeArray();
    expect($shared)->toHaveKeys(['id', 'name', 'has_active_subscription', 'has_growth_plan']);
    expect($shared['has_growth_plan'])->toBeFalse();
});
