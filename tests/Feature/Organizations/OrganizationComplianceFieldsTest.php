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
use function Pest\Laravel\seed;

it('allows an administrator to update compliance fields', function () {
    seed(Database\Seeders\PermissionsSeeder::class);

    $user = User::factory()->create();
    $organization = Organization::factory()->create([
        'tax_id' => null,
        'coc_number' => null,
        'address_line1' => null,
        'address_line2' => null,
        'address_city' => null,
        'address_state' => null,
        'address_postal_code' => null,
        'address_country' => null,
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
        'address_line1' => '123 Compliance Way',
        'address_line2' => 'Suite 400',
        'address_city' => 'Metropolis',
        'address_state' => 'NY',
        'address_postal_code' => '12345',
        'address_country' => 'US',
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
        'address_line1',
        'address_line2',
        'address_city',
        'address_state',
        'address_postal_code',
        'address_country',
    ]))->toMatchArray($payload);
});
