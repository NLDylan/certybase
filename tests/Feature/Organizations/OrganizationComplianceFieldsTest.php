<?php

declare(strict_types=1);

use App\Enums\OrganizationUserStatus;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\User;
use Illuminate\Support\Facades\Session;
use Spatie\Permission\PermissionRegistrar;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\put;

it('allows an administrator to update compliance fields', function () {
    seed(Database\Seeders\PermissionsSeeder::class);

    $user = User::factory()->create();
    $organization = Organization::factory()->create([
        'tax_id' => null,
        'coc_number' => null,
        'postal_address' => null,
    ]);

    OrganizationUser::query()->create([
        'organization_id' => $organization->getKey(),
        'user_id' => $user->getKey(),
        'status' => OrganizationUserStatus::Active,
    ]);

    /** @var PermissionRegistrar $registrar */
    $registrar = app(PermissionRegistrar::class);
    $registrar->setPermissionsTeamId($organization->getKey());
    $user->assignRole('Administrator');
    $registrar->setPermissionsTeamId(null);

    Session::put('organization_id', $organization->getKey());

    actingAs($user);

    $payload = [
        'name' => 'Compliance Ready LLC',
        'email' => 'hello@compliance.test',
        'phone_number' => '+1 (555) 222-3333',
        'website' => 'https://compliance.test',
        'tax_id' => 'VAT-998877',
        'coc_number' => 'COC-556677',
        'postal_address' => "123 Compliance Way\nSuite 400\nMetropolis, NY 12345",
    ];

    put(route('organization.settings.update'), $payload)
        ->assertRedirect(route('organization.settings'));

    $organization->refresh();

    expect($organization->only([
        'name',
        'email',
        'phone_number',
        'website',
        'tax_id',
        'coc_number',
        'postal_address',
    ]))->toMatchArray($payload);
});
