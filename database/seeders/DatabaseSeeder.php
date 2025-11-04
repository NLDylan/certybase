<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed base permissions & roles first
        $this->call([
            PermissionsSeeder::class,
        ]);

        // Ensure the test user exists
        User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => 'password',
            ]
        );

        // Seed organizations and assign memberships/roles
        $this->call([
            OrganizationSeeder::class,
        ]);
    }
}
