<?php

namespace App\Services;

use App\Enums\OrganizationStatus;
use App\Enums\OrganizationUserStatus;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Spatie\Permission\Models\Role;

class OrganizationService
{
    /**
     * Create a new organization and add the creator as an administrator.
     */
    public function create(array $data, string $userId): Organization
    {
        return DB::transaction(function () use ($data, $userId) {
            // Create the organization
            $organization = Organization::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'email' => $data['email'] ?? null,
                'phone_number' => $data['phone_number'] ?? null,
                'website' => $data['website'] ?? null,
                'status' => $data['status'] ?? OrganizationStatus::Active,
                'settings' => $data['settings'] ?? [],
            ]);

            // Add creator as administrator
            $this->addUserToOrganization($organization->id, $userId, 'Administrator', accept: true);

            return $organization;
        });
    }

    /**
     * Invite a user to the organization.
     */
    public function inviteUser(string $organizationId, string $email, ?string $role = null): OrganizationUser
    {
        // Find user by email
        $user = User::where('email', $email)->firstOrFail();

        // Check if user is already a member
        $existingMembership = OrganizationUser::where('organization_id', $organizationId)
            ->where('user_id', $user->id)
            ->first();

        if ($existingMembership) {
            throw new \Exception('User is already a member of this organization.');
        }

        // Create invitation
        $membership = OrganizationUser::create([
            'organization_id' => $organizationId,
            'user_id' => $user->id,
            'status' => OrganizationUserStatus::Pending,
            'invited_at' => now(),
            'invited_role' => $role ?? 'Viewer',
        ]);

        $membership->generateInvitationToken();

        // TODO: Dispatch event to send invitation email

        return $membership;
    }

    /**
     * Accept an invitation to join an organization.
     */
    public function acceptInvitation(string $token, string $userId): OrganizationUser
    {
        $membership = OrganizationUser::where('invitation_token', $token)
            ->where('user_id', $userId)
            ->firstOrFail();

        if ($membership->isInvitationExpired()) {
            throw new \Exception('Invitation has expired.');
        }

        if ($membership->isActive()) {
            throw new \Exception('Invitation has already been accepted.');
        }

        // Accept the invitation
        $membership->acceptInvitation();

        // Assign role if specified
        if ($membership->invited_role) {
            $this->assignRoleToUser($membership->organization_id, $userId, $membership->invited_role);
        }

        return $membership;
    }

    /**
     * Remove a user from the organization.
     */
    public function removeUser(string $organizationId, string $userId): void
    {
        $user = User::findOrFail($userId);
        $registrar = app(\Spatie\Permission\PermissionRegistrar::class);
        $originalTeamId = $registrar->getPermissionsTeamId();

        // Set organization context
        $registrar->setPermissionsTeamId($organizationId);

        // Get all roles for this organization
        $roles = $user->roles()->get();
        foreach ($roles as $role) {
            $user->removeRole($role);
        }

        // Restore original team ID
        $registrar->setPermissionsTeamId($originalTeamId);

        // Delete the membership
        OrganizationUser::where('organization_id', $organizationId)
            ->where('user_id', $userId)
            ->delete();
    }

    /**
     * Switch the user's current organization context.
     */
    public function switchOrganization(string $userId, string $organizationId): void
    {
        $user = User::findOrFail($userId);

        // Verify user has access
        if (! $user->hasAccessToOrganization($organizationId)) {
            throw new \Exception('User does not have access to this organization.');
        }

        // Update session
        Session::put('organization_id', $organizationId);

        // Set permissions team ID
        app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId($organizationId);
    }

    /**
     * Add a user to an organization with a specific role.
     */
    protected function addUserToOrganization(
        string $organizationId,
        string $userId,
        string $role = 'Viewer',
        bool $accept = false
    ): OrganizationUser {
        $membership = OrganizationUser::firstOrCreate(
            [
                'organization_id' => $organizationId,
                'user_id' => $userId,
            ],
            [
                'status' => $accept ? OrganizationUserStatus::Active : OrganizationUserStatus::Pending,
                'accepted_at' => $accept ? now() : null,
                'invited_role' => $role,
            ]
        );

        if ($accept) {
            $this->assignRoleToUser($organizationId, $userId, $role);
        }

        return $membership;
    }

    /**
     * Assign a role to a user within an organization.
     */
    protected function assignRoleToUser(string $organizationId, string $userId, string $roleName): void
    {
        $user = User::findOrFail($userId);
        $registrar = app(\Spatie\Permission\PermissionRegistrar::class);
        $originalTeamId = $registrar->getPermissionsTeamId();

        // Set organization context
        $registrar->setPermissionsTeamId($organizationId);

        // Find or create role within this organization
        $role = Role::firstOrCreate(
            [
                'name' => $roleName,
                'guard_name' => 'web',
                'organization_id' => $organizationId,
            ]
        );

        // If role has no permissions, sync them based on role name
        if ($role->permissions->isEmpty()) {
            $this->syncRolePermissions($role, $roleName);
        }

        // Assign role
        $user->assignRole($role);

        // Restore original team ID
        $registrar->setPermissionsTeamId($originalTeamId);
    }

    /**
     * Sync permissions to a role based on role name.
     */
    protected function syncRolePermissions(Role $role, string $roleName): void
    {
        $allPermissions = \Spatie\Permission\Models\Permission::all();

        match ($roleName) {
            'Administrator' => $role->syncPermissions($allPermissions),
            'Editor' => $role->syncPermissions($allPermissions->filter(function ($permission) {
                return ! in_array($permission->name, ['delete-organization', 'manage-billing']);
            })),
            'Viewer' => $role->syncPermissions($allPermissions->filter(function ($permission) {
                return str_starts_with($permission->name, 'view-') || $permission->name === 'download-certificates';
            })),
            default => $role->syncPermissions([]),
        };
    }
}
