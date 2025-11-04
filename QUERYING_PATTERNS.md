# Querying Patterns for URL-Based Organization Routing

## Overview

All organization-scoped routes follow this pattern:
```
/organizations/{organization_id}/designs
/organizations/{organization_id}/campaigns
/organizations/{organization_id}/certificates
```

The `OrganizationContext` middleware validates access and sets up the organization context.

## Getting Organization ID from Route

All controllers extend `Controller` which provides helper methods:

```php
// Get organization ID
$organizationId = $this->currentOrganizationIdOrFail();

// Get organization model
$organization = $this->currentOrganizationOrFail();
```

## Querying Patterns

### ✅ Organization-Scoped Models

These models **ARE scoped by organization_id**:

- **Designs** - `organization_id` foreign key
- **Campaigns** - `organization_id` foreign key
- **Certificates** - `organization_id` foreign key
- **OrganizationUser** (pivot) - `organization_id` foreign key

**Example - Designs:**

```php
$organizationId = $this->currentOrganizationIdOrFail();

// List designs for organization
$designs = Design::query()
    ->where('organization_id', $organizationId)
    ->with(['creator', 'organization'])
    ->latest()
    ->paginate(15);

// Create design scoped to organization
$design = Design::create([
    'organization_id' => $organizationId, // Always from URL
    'creator_id' => $request->user()->id,
    'name' => $request->name,
]);

// Verify design belongs to organization
$design = Design::find($id);
if ($design->organization_id !== $organizationId) {
    abort(404); // Prevent cross-organization access
}
```

**Example - Campaigns:**

```php
$organizationId = $this->currentOrganizationIdOrFail();

// List campaigns with relationships
$campaigns = Campaign::query()
    ->where('organization_id', $organizationId)
    ->with(['design', 'creator', 'organization'])
    ->withCount('certificates')
    ->latest()
    ->paginate(15);

// Verify related model belongs to organization
$design = Design::where('id', $designId)
    ->where('organization_id', $organizationId) // Important!
    ->firstOrFail();
```

### ❌ Global Models (NOT Scoped by Organization)

These models are **GLOBAL** and should NOT be filtered by organization_id:

- **Users** - Users belong to multiple organizations
- **DesignTemplates** - Admin-managed, available to all organizations

**Example - Users:**

```php
$organizationId = $this->currentOrganizationIdOrFail();
$organization = $this->currentOrganizationOrFail();

// ❌ WRONG - Don't scope User queries by organization
// $users = User::where('organization_id', $organizationId)->get(); // WRONG!

// ✅ CORRECT - Query users through organization relationship
$users = $organization->users()
    ->wherePivot('status', 'active')
    ->withPivot(['status', 'invited_at', 'invited_role'])
    ->get();

// ✅ CORRECT - Or query via pivot table
$memberships = OrganizationUser::query()
    ->where('organization_id', $organizationId)
    ->where('status', 'active')
    ->with('user')
    ->get();

// ✅ CORRECT - Search users globally (not scoped)
$user = User::where('email', $email)->firstOrFail();

// Then create membership (scoped to organization)
OrganizationUser::create([
    'organization_id' => $organizationId,
    'user_id' => $user->id,
    'status' => OrganizationUserStatus::Pending,
]);
```

**Example - Design Templates:**

```php
// ❌ WRONG - Don't filter by organization
// $templates = DesignTemplate::where('organization_id', $organizationId)->get();

// ✅ CORRECT - Templates are global/admin-managed
$templates = DesignTemplate::query()
    ->where('is_active', true)
    ->where('is_public', true)
    ->get();

// When using template, create design scoped to organization
$design = $template->createDesignFromTemplate($organizationId, $userId);
```

## Security Pattern

**Always verify ownership** before showing/editing resources:

```php
public function show(Design $design): Response
{
    $organizationId = $this->currentOrganizationIdOrFail();

    // Security check: ensure resource belongs to organization
    if ($design->organization_id !== $organizationId) {
        abort(404); // Don't reveal that resource exists in another org
    }

    return Inertia::render('Designs/Show', ['design' => $design]);
}
```

## Route Model Binding

Laravel's route model binding works, but you still need to verify organization ownership:

```php
// Route: /organizations/{organization_id}/designs/{design}
public function show(Design $design): Response
{
    $organizationId = $this->currentOrganizationIdOrFail();

    // Still verify ownership (binding doesn't prevent cross-org access)
    if ($design->organization_id !== $organizationId) {
        abort(404);
    }

    return Inertia::render('Designs/Show', ['design' => $design]);
}
```

## Common Patterns

### Pattern 1: List with Relationships

```php
$items = Model::query()
    ->where('organization_id', $organizationId)
    ->with(['relationship1', 'relationship2'])
    ->latest()
    ->paginate(15);
```

### Pattern 2: Create Scoped to Organization

```php
$item = Model::create([
    'organization_id' => $organizationId, // From URL
    'creator_id' => $request->user()->id,
    ...$validated,
]);
```

### Pattern 3: Verify Related Model

```php
// When using a related model (e.g., design for campaign)
$related = RelatedModel::where('id', $relatedId)
    ->where('organization_id', $organizationId) // Verify scope
    ->firstOrFail();
```

### Pattern 4: Users Through Organization

```php
// Get users via organization relationship
$users = $organization->users()
    ->wherePivot('status', OrganizationUserStatus::Active)
    ->get();
```

## Summary

| Model | Scoped by Organization? | Query Pattern |
|-------|------------------------|---------------|
| Design | ✅ Yes | `->where('organization_id', $organizationId)` |
| Campaign | ✅ Yes | `->where('organization_id', $organizationId)` |
| Certificate | ✅ Yes | `->where('organization_id', $organizationId)` |
| OrganizationUser | ✅ Yes (pivot) | `->where('organization_id', $organizationId)` |
| User | ❌ No (global) | Query via `$organization->users()` or `User::find()` |
| DesignTemplate | ❌ No (global) | No organization filter |
| Organization | ❌ No (global) | Query directly |
