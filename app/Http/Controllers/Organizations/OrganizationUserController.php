<?php

namespace App\Http\Controllers\Organizations;

use App\Http\Controllers\Controller;
use App\Models\OrganizationUser;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationUserController extends Controller
{
    /**
     * Display a listing of users in the current organization.
     *
     * NOTE: Users are GLOBAL (not scoped by organization).
     * But organization_user pivot IS scoped by organization.
     */
    public function index(): Response
    {
        $organizationId = $this->currentOrganizationIdOrFail();
        $organization = $this->currentOrganizationOrFail();

        // Get users through the organization relationship
        // This automatically scopes to the organization from URL
        $users = $organization->users()
            ->wherePivot('status', 'active')
            ->withPivot(['status', 'invited_at', 'accepted_at', 'invited_role'])
            ->with('organizationMemberships')
            ->get();

        // Or query via the pivot table directly (alternative approach)
        // $memberships = OrganizationUser::query()
        //     ->where('organization_id', $organizationId)
        //     ->where('status', 'active')
        //     ->with('user')
        //     ->get();

        return Inertia::render('Organizations/Users/Index', [
            'users' => $users,
            'organization' => $organization,
        ]);
    }

    /**
     * Invite a user to the organization.
     *
     * Users are searched globally, but invitation is scoped to organization.
     */
    public function invite(Request $request)
    {
        $organizationId = $this->currentOrganizationIdOrFail();
        $organization = $this->currentOrganizationOrFail();

        $validated = $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
            'role' => ['nullable', 'string'],
        ]);

        // Find user globally (users are not scoped by organization)
        $user = User::where('email', $validated['email'])->firstOrFail();

        // Check if user is already a member
        $existingMembership = OrganizationUser::where('organization_id', $organizationId)
            ->where('user_id', $user->id)
            ->first();

        if ($existingMembership) {
            return back()->withErrors(['email' => 'User is already a member of this organization.']);
        }

        // Create invitation (scoped to organization from URL)
        $membership = OrganizationUser::create([
            'organization_id' => $organizationId,
            'user_id' => $user->id,
            'status' => \App\Enums\OrganizationUserStatus::Pending,
            'invited_at' => now(),
            'invited_role' => $validated['role'] ?? null,
        ]);

        $membership->generateInvitationToken();

        // TODO: Send invitation email

        return redirect()->route('organizations.users.index', [
            'organization_id' => $organizationId,
        ])->with('success', 'Invitation sent successfully.');
    }

    /**
     * Remove a user from the organization.
     *
     * NOTE: This only removes the membership, NOT the user itself.
     */
    public function destroy(User $user)
    {
        $organizationId = $this->currentOrganizationIdOrFail();

        // Delete the membership (not the user - users are global)
        OrganizationUser::where('organization_id', $organizationId)
            ->where('user_id', $user->id)
            ->delete();

        return redirect()->route('organizations.users.index', [
            'organization_id' => $organizationId,
        ])->with('success', 'User removed from organization.');
    }
}
