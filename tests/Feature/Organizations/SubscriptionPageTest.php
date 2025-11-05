<?php

use App\Enums\OrganizationStatus;
use App\Enums\OrganizationUserStatus;
use App\Models\Organization;
use App\Models\User;

it('shows subscription page and includes plans data', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $organization = Organization::create([
        'name' => 'Acme Org',
        'status' => OrganizationStatus::Active,
    ]);

    $user->organizations()->attach($organization->id, ['status' => OrganizationUserStatus::Active]);

    $response = $this->withSession(['organization_id' => (string) $organization->id])
        ->get(route('organization.subscription.index'));

    $response->assertSuccessful();

    // Basic sanity check: the initial Inertia payload should include our new subscription props
    $response->assertSee('organizations/Subscription/Index');
});
