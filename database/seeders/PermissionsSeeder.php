<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // Organization permissions
            'view-organization',
            'update-organization',
            'delete-organization',
            'manage-billing',

            // User permissions
            'view-users',
            'invite-users',
            'update-users',
            'remove-users',

            // Design permissions
            'view-designs',
            'create-designs',
            'update-designs',
            'delete-designs',
            'publish-designs',

            // Campaign permissions
            'view-campaigns',
            'create-campaigns',
            'update-campaigns',
            'delete-campaigns',
            'execute-campaigns',

            // Certificate permissions
            'view-certificates',
            'create-certificates',
            'update-certificates',
            'delete-certificates',
            'revoke-certificates',
            'download-certificates',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles (these will be created per-organization with team scoping)
        // Note: Actual role assignment happens when organizations are created
        // This seeder just ensures permissions exist in the database

        $this->command->info('Permissions created successfully!');
        $this->command->info('Roles will be created per-organization when organizations are created.');
    }
}
