<?php

namespace App\Policies;

use App\Models\DesignTemplate;
use App\Models\User;

class DesignTemplatePolicy
{
    /**
     * Determine if the user can view any design templates.
     */
    public function viewAny(User $user): bool
    {
        return true; // All authenticated users can view public templates
    }

    /**
     * Determine if the user can view the design template.
     */
    public function view(User $user, DesignTemplate $designTemplate): bool
    {
        // Public templates are viewable by all
        if ($designTemplate->is_public) {
            return true;
        }

        // Non-public templates require admin access
        return $user->isAdmin();
    }

    /**
     * Determine if the user can create design templates.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine if the user can update the design template.
     */
    public function update(User $user, DesignTemplate $designTemplate): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine if the user can delete the design template.
     */
    public function delete(User $user, DesignTemplate $designTemplate): bool
    {
        return $user->isAdmin();
    }
}
