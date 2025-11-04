<?php

namespace App\Http\Controllers\Organizations;

use App\Http\Controllers\Controller;
use App\Http\Requests\InviteUserRequest;
use App\Models\User;
use App\Services\OrganizationService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationUserController extends Controller
{
    public function __construct(
        protected OrganizationService $organizationService
    ) {}

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
            ->wherePivot('status', \App\Enums\OrganizationUserStatus::Active)
            ->withPivot(['status', 'invited_at', 'accepted_at', 'invited_role'])
            ->with('organizationMemberships')
            ->get();

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
    public function invite(InviteUserRequest $request): RedirectResponse
    {
        $organizationId = $this->currentOrganizationIdOrFail();
        $organization = $this->currentOrganizationOrFail();

        // Check authorization
        $this->authorize('inviteUsers', $organization);

        try {
            $this->organizationService->inviteUser(
                $organizationId,
                $request->validated()['email'],
                $request->validated()['role'] ?? null
            );

            return redirect()->route('organizations.users.index', [
                'organization_id' => $organizationId,
            ])->with('success', 'Invitation sent successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['email' => $e->getMessage()]);
        }
    }

    /**
     * Remove a user from the organization.
     *
     * NOTE: This only removes the membership, NOT the user itself.
     */
    public function destroy(User $user): RedirectResponse
    {
        $organizationId = $this->currentOrganizationIdOrFail();
        $organization = $this->currentOrganizationOrFail();

        // Check authorization
        $this->authorize('inviteUsers', $organization);

        $this->organizationService->removeUser($organizationId, $user->id);

        return redirect()->route('organizations.users.index', [
            'organization_id' => $organizationId,
        ])->with('success', 'User removed from organization.');
    }
}
