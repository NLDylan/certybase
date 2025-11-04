<?php

use App\Enums\OrganizationStatus;
use App\Enums\OrganizationUserStatus;
use App\Models\Organization;
use App\Models\User;

it('redirects guests to login', function () {
    $response = $this->get(route('organizations.index'));
    $response->assertRedirect(route('login'));
});

it('shows organizations list for authenticated user', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $orgA = Organization::create([
        'name' => 'Alpha Org',
        'status' => OrganizationStatus::Active,
    ]);

    $orgB = Organization::create([
        'name' => 'Beta Org',
        'status' => OrganizationStatus::Active,
    ]);

    $user->organizations()->attach($orgA->id, ['status' => OrganizationUserStatus::Active]);
    $user->organizations()->attach($orgB->id, ['status' => OrganizationUserStatus::Active]);

    $response = $this->get(route('organizations.index'));
    $response->assertSuccessful();
    $response->assertSee('Alpha Org');
    $response->assertSee('Beta Org');
});
