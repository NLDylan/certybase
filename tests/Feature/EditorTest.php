<?php

declare(strict_types=1);

use App\Enums\DesignStatus;
use App\Enums\OrganizationStatus;
use App\Enums\OrganizationUserStatus;
use App\Models\Design;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Session;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

use function Pest\Laravel\actingAs;

function setupEditorTestUser(): array
{
    $user = User::factory()->create();

    $organization = Organization::query()->create([
        'name' => 'Test Org',
        'status' => OrganizationStatus::Active,
        'settings' => [],
    ]);

    $organization->users()->syncWithoutDetaching([
        $user->getKey() => [
            'status' => OrganizationUserStatus::Active->value,
        ],
    ]);

    /** @var PermissionRegistrar $registrar */
    $registrar = app(PermissionRegistrar::class);
    $registrar->setPermissionsTeamId($organization->getKey());

    Role::findOrCreate('Administrator', 'web');
    $user->assignRole('Administrator');

    $registrar->setPermissionsTeamId(null);

    Session::put('organization_id', $organization->getKey());

    return [$user, $organization];
}

it('renders the editor page with design variables', function () {
    [$user, $organization] = setupEditorTestUser();
    actingAs($user);

    $design = Design::query()->create([
        'organization_id' => $organization->getKey(),
        'creator_id' => $user->getKey(),
        'name' => 'Certificate Template - Simple',
        'status' => DesignStatus::Draft,
        'design_data' => [
            'objects' => [
                [
                    'type' => 'IText',
                    'text' => '{{ firstname }}',
                    'template' => 'Your Text Here',
                ],
            ],
        ],
        'variables' => ['firstname'],
        'settings' => [],
    ]);

    $this->get(route('editor.show', $design))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('editor/[id]')
            ->where('design.id', $design->getKey())
            ->where('design.variables', ['firstname'])
        );
});
