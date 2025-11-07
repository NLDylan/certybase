<?php

namespace Database\Seeders;

use App\Enums\CampaignStatus;
use App\Enums\OrganizationStatus;
use App\Enums\OrganizationUserStatus;
use App\Models\Campaign;
use App\Models\Design;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class OrganizationSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure test user exists
        /** @var User|null $user */
        $user = User::where('email', 'test@example.com')->first();

        // Create 5 organizations
        $organizations = collect(range(1, 5))->map(function (int $i) {
            return Organization::create([
                'name' => 'Organization '.$i,
                'status' => OrganizationStatus::Active,
                'settings' => [],
            ]);
        });

        if ($user) {
            // Assign first two organizations to the test user as Administrator
            $organizations->take(2)->each(function (Organization $organization) use ($user) {
                // Attach membership as active
                $organization->users()->syncWithoutDetaching([
                    $user->getKey() => ['status' => OrganizationUserStatus::Active->value],
                ]);

                // Assign Administrator role scoped to this organization (team)
                /** @var PermissionRegistrar $registrar */
                $registrar = app(PermissionRegistrar::class);
                $registrar->setPermissionsTeamId($organization->getKey());
                $user->assignRole('Administrator');
                // Optionally clear team context after assignment
                $registrar->setPermissionsTeamId(null);
            });
        }

        // Seed a sample campaign for the first organization to aid demos
        $primaryOrganization = $organizations->first();

        if ($primaryOrganization) {
            $design = Design::factory()
                ->for($primaryOrganization)
                ->state([
                    'creator_id' => $user?->id,
                    'variables' => [
                        'course' => '{{ course }}',
                        'issued_on' => '{{ issued_on }}',
                    ],
                ])
                ->create();

            Campaign::factory()
                ->for($primaryOrganization)
                ->for($design)
                ->state([
                    'creator_id' => $user?->id,
                    'status' => CampaignStatus::Draft,
                    'variable_mapping' => [
                        'recipient_name' => 'full_name',
                        'recipient_email' => 'email',
                        'variables' => [
                            'course' => 'course',
                            'issued_on' => 'issued_on',
                        ],
                    ],
                ])
                ->create();
        }
    }
}
