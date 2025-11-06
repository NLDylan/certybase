<?php

declare(strict_types=1);

use App\Enums\DesignStatus;
use App\Enums\OrganizationStatus;
use App\Enums\OrganizationUserStatus;
use App\Models\Design;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Session;
use Spatie\Permission\PermissionRegistrar;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;
use function Pest\Laravel\getJson;

function setupOrgUser(): array {
    $user = User::factory()->create();
    $organization = Organization::query()->create([
        'name' => 'Test Org',
        'status' => OrganizationStatus::Active,
        'settings' => [],
    ]);

    // Attach membership
    $organization->users()->syncWithoutDetaching([
        $user->getKey() => ['status' => OrganizationUserStatus::Active->value],
    ]);

    // Set team context and grant Administrator role
    /** @var PermissionRegistrar $registrar */
    $registrar = app(PermissionRegistrar::class);
    $registrar->setPermissionsTeamId($organization->getKey());
    $user->assignRole('Administrator');
    $registrar->setPermissionsTeamId(null);

    // Set current organization in session
    Session::put('organization_id', $organization->getKey());

    return [$user, $organization];
}

it('uploads image to a design collection', function () {
    [$user, $organization] = setupOrgUser();
    actingAs($user);

    $design = Design::query()->create([
        'organization_id' => $organization->getKey(),
        'creator_id' => $user->getKey(),
        'name' => 'My Design',
        'status' => DesignStatus::Draft,
        'design_data' => [],
        'variables' => [],
        'settings' => [],
    ]);

    $file = UploadedFile::fake()->image('test.png', 10, 10);

    $response = postJson(route('media.store'), [
        'model_type' => 'design',
        'model_id' => $design->getKey(),
        'collection' => 'canvas_images',
        'file' => $file,
    ]);

    $response->assertSuccessful();
    $response->assertJsonStructure(['id', 'url']);
});

it('validates download-from-url input', function () {
    [$user, $organization] = setupOrgUser();
    actingAs($user);

    $design = Design::query()->create([
        'organization_id' => $organization->getKey(),
        'creator_id' => $user->getKey(),
        'name' => 'My Design',
        'status' => DesignStatus::Draft,
        'design_data' => [],
        'variables' => [],
        'settings' => [],
    ]);

    $response = postJson(route('media.from-url'), [
        'model_type' => 'design',
        'model_id' => $design->getKey(),
        'collection' => 'canvas_images',
        'url' => 'not-a-url',
    ]);

    $response->assertStatus(422);
});

it('shows media with auth and returns metadata', function () {
    [$user, $organization] = setupOrgUser();
    actingAs($user);

    $design = Design::query()->create([
        'organization_id' => $organization->getKey(),
        'creator_id' => $user->getKey(),
        'name' => 'My Design',
        'status' => DesignStatus::Draft,
        'design_data' => [],
        'variables' => [],
        'settings' => [],
    ]);

    $file = UploadedFile::fake()->image('test.png', 10, 10);
    $upload = postJson(route('media.store'), [
        'model_type' => 'design',
        'model_id' => $design->getKey(),
        'collection' => 'canvas_images',
        'file' => $file,
    ])->assertSuccessful();

    $mediaId = (int) $upload->json('id');

    $show = getJson(route('media.show', ['media' => $mediaId]));
    $show->assertSuccessful();
    $show->assertJson(fn ($json) => $json->hasAny(['url', 'inline']));
});


