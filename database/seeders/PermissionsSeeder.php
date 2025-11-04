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
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Create standard roles and assign permissions
        // Administrator: all permissions
        $adminRole = Role::findOrCreate('Administrator', 'web');
        $adminRole->syncPermissions(Permission::all());

        // Editor: can view everything, create/update (but not delete org/billing), execute campaigns
        $editorPermissions = [
            // View permissions across resources
            'view-organization',
            'view-users',
            'view-designs',
            'view-campaigns',
            'view-certificates',
            'download-certificates',

            // Modify non-destructive actions
            'create-designs',
            'update-designs',
            'publish-designs',

            'create-campaigns',
            'update-campaigns',
            'execute-campaigns',

            'create-certificates',
            'update-certificates',
        ];
        $editorRole = Role::findOrCreate('Editor', 'web');
        $editorRole->syncPermissions(Permission::whereIn('name', $editorPermissions)->get());

        // Viewer: read-only access
        $viewerPermissions = [
            'view-organization',
            'view-users',
            'view-designs',
            'view-campaigns',
            'view-certificates',
            'download-certificates',
        ];
        $viewerRole = Role::findOrCreate('Viewer', 'web');
        $viewerRole->syncPermissions(Permission::whereIn('name', $viewerPermissions)->get());

        $this->command->info('Permissions and standard roles created successfully!');
    }
}
