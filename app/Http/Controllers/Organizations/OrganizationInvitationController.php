<?php

namespace App\Http\Controllers\Organizations;

use App\Http\Controllers\Controller;
use App\Models\OrganizationUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationInvitationController extends Controller
{
    /**
     * Display the invitation details.
     */
    public function show(string $token): Response
    {
        $membership = OrganizationUser::where('invitation_token', $token)
            ->with(['organization', 'user'])
            ->firstOrFail();

        if ($membership->isInvitationExpired()) {
            abort(410, 'This invitation has expired.');
        }

        if ($membership->isActive()) {
            return redirect()->route('organizations.index')
                ->with('info', 'You have already accepted this invitation.');
        }

        // Check if user is authenticated and matches the invitation
        $isCurrentUser = Auth::check() && Auth::id() === $membership->user_id;

        return Inertia::render('organizations/InvitationAccept', [
            'invitation' => [
                'token' => $token,
                'organization' => [
                    'id' => $membership->organization->id,
                    'name' => $membership->organization->name,
                ],
                'invited_role' => $membership->invited_role,
                'invited_at' => $membership->invited_at,
                'is_current_user' => $isCurrentUser,
            ],
        ]);
    }

    /**
     * Accept the invitation.
     */
    public function accept(string $token): RedirectResponse
    {
        $user = Auth::user();

        if (! $user) {
            return redirect()->route('login')->with('error', 'Please log in to accept this invitation.');
        }

        $membership = OrganizationUser::where('invitation_token', $token)
            ->where('user_id', $user->id)
            ->firstOrFail();

        if ($membership->isInvitationExpired()) {
            return redirect()->route('organizations.index')
                ->with('error', 'This invitation has expired.');
        }

        if ($membership->isActive()) {
            return redirect()->route('organizations.index')
                ->with('info', 'You have already accepted this invitation.');
        }

        // Accept the invitation
        $membership->acceptInvitation();

        // Assign role if specified
        if ($membership->invited_role) {
            $registrar = app(\Spatie\Permission\PermissionRegistrar::class);
            $originalTeamId = $registrar->getPermissionsTeamId();
            $registrar->setPermissionsTeamId($membership->organization_id);

            $role = \Spatie\Permission\Models\Role::firstOrCreate(
                [
                    'name' => $membership->invited_role,
                    'guard_name' => 'web',
                    'organization_id' => $membership->organization_id,
                ]
            );

            $user->assignRole($role);
            $registrar->setPermissionsTeamId($originalTeamId);
        }

        // Switch to the organization
        $user->switchOrganization($membership->organization_id);

        return redirect()->route('organizations.dashboard', [
            'organization_id' => $membership->organization_id,
        ])->with('success', 'Invitation accepted successfully.');
    }

    /**
     * Decline the invitation.
     */
    public function decline(string $token): RedirectResponse
    {
        $user = Auth::user();

        if (! $user) {
            return redirect()->route('login')->with('error', 'Please log in to decline this invitation.');
        }

        $membership = OrganizationUser::where('invitation_token', $token)
            ->where('user_id', $user->id)
            ->firstOrFail();

        if ($membership->isActive()) {
            return redirect()->route('organizations.index')
                ->with('info', 'You have already accepted this invitation.');
        }

        // Delete the invitation
        $membership->delete();

        return redirect()->route('organizations.index')
            ->with('success', 'Invitation declined.');
    }
}
