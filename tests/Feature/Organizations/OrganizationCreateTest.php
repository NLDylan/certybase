<?php

declare(strict_types=1);

use App\Enums\OrganizationUserStatus;
use App\Models\Organization;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\post;
use function Pest\Laravel\seed;

it('creates an organization with extended fields', function () {
    seed(Database\Seeders\PermissionsSeeder::class);
    config(['app.key' => 'base64:'.base64_encode(random_bytes(32))]);
    $this->withoutVite();

    $user = User::factory()->create();

    actingAs($user);

    $payload = [
        'name' => 'Certybase Compliance BV',
        'description' => 'Handles certification issuance for the EU region.',
        'email' => 'contact@certybase.test',
        'phone_number' => '+31 20 123 4567',
        'website' => 'https://certybase.test',
        'tax_id' => 'VAT-123456',
        'coc_number' => 'COC-789012',
        'address_line1' => 'Keizersgracht 100',
        'address_line2' => 'Unit 4B',
        'address_city' => 'Amsterdam',
        'address_state' => 'NH',
        'address_postal_code' => '1015AB',
        'address_country' => 'NL',
    ];

    post(route('organizations.store'), $payload)
        ->assertRedirect(route('dashboard'));

    $organization = Organization::query()->first();

    expect($organization)->not->toBeNull();
    expect($organization->only([
        'name',
        'description',
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

    expect($organization->users)->toHaveCount(1);
    expect($organization->users->first()?->pivot->status)->toBe(OrganizationUserStatus::Active);
});
