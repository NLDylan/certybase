# Certificate Management SaaS - Implementation Plan

## Project Overview

A Laravel 12 SaaS application for creating and managing digital certificates. Organizations can create custom certificate designs, run campaigns, and issue certificates for events like training completion, courses, or achievements.

**Core Entities:**
- **Organizations** - Billable entities that own designs and certificates
- **Users** - Can belong to multiple organizations with different roles
- **Designs** - Certificate templates with customizable variables
- **Campaigns** - Batch certificate generation for multiple recipients
- **Certificates** - Individual certificates issued to recipients
- **Design Templates** - Admin-managed templates available to all organizations

**Already Installed:**
- ✅ Laravel 12 + Inertia.js + Vue 3 + Tailwind 4
- ✅ Spatie Permissions (teams not yet configured)
- ✅ Spatie Media Library
- ✅ Laravel Cashier
- ✅ Laravel Fortify (authentication)
- ✅ Laravel Horizon (queue monitoring)
- ✅ Basic migrations: users, cache, jobs, media, permissions, subscriptions

## Phase 1: Database Structure

**Migration Best Practices Applied:**
- ✅ All media files (logos, previews, PDFs) stored via Spatie Media Library - NO database columns
- ✅ PostgreSQL jsonb type for JSON fields (better indexing and performance)
- ✅ Composite indexes for common query patterns (organization_id + status, etc.)
- ✅ Proper foreign key constraints with appropriate onDelete actions (cascade, set null, restrict)
- ✅ Unique constraints on email, stripe_id, invitation_token, verification_token
- ✅ Indexes on frequently filtered/searched columns (status, email, dates)
- ✅ Clear documentation of foreign key relationships and their cascade behavior

### 1.1 Create Organizations Table

**File:** `database/migrations/YYYY_MM_DD_create_organizations_table.php`

**Fields:**
- `id` - uuid primary
- `name` - string, required
- `description` - text, nullable
- `email` - string, nullable, unique
- `phone_number` - string, nullable
- `website` - string, nullable
- `status` - enum: active, suspended (default: active)
- `settings` - jsonb (theme, branding, defaults)
- `stripe_id` - string, nullable, unique (Cashier)
- `pm_type` - string, nullable (Cashier)
- `pm_last_four` - string, nullable (Cashier)
- `trial_ends_at` - timestamp, nullable (Cashier)
- `timestamps`

**Indexes:**
- `status`
- `email` (unique)
- `stripe_id` (unique)
- composite: `status + created_at` (for filtering active orgs by date)

**Foreign Keys:** None (root table)

**Media Collections:** Logo stored via Spatie Media Library (`logo` collection), NOT as database column

**Notes:** Organizations are the billable entities (use Billable trait)

### 1.2 Create Organization User Pivot Table

**File:** `database/migrations/YYYY_MM_DD_create_organization_user_table.php`

**Fields:**
- `id` - auto-increment
- `organization_id` - uuid, foreign -> organizations.id (cascade)
- `user_id` - uuid, foreign -> users.id (cascade)
- `status` - enum: active, inactive, pending (default: pending)
- `invited_at` - timestamp, nullable
- `accepted_at` - timestamp, nullable
- `invitation_token` - string, nullable, unique
- `invitation_expires_at` - timestamp, nullable
- `invited_role` - string, nullable
- `timestamps`

**Indexes:**
- unique: `organization_id + user_id` (prevents duplicate memberships)
- composite: `user_id + status` (for finding user's active orgs)
- composite: `organization_id + status` (for filtering org members)
- `invitation_token` (unique, for invitation lookups)
- `status` (for filtering pending invitations)

**Foreign Keys:**
- `organization_id` -> organizations.id (onDelete: cascade)
- `user_id` -> users.id (onDelete: cascade)

### 1.3 Create Designs Table

**File:** `database/migrations/YYYY_MM_DD_create_designs_table.php`

**Fields:**
- `id` - uuid primary
- `organization_id` - uuid, foreign -> organizations.id (cascade)
- `creator_id` - uuid, nullable, foreign -> users.id (set null)
- `name` - string, required
- `description` - text, nullable
- `design_data` - jsonb (Fabric.js canvas data - use jsonb for PostgreSQL indexing)
- `variables` - jsonb (list of dynamic variables like {name}, {date})
- `settings` - jsonb (page size, orientation, fonts)
- `status` - enum: draft, active, inactive, archived (default: draft)
- `timestamps`

**Indexes:**
- composite: `organization_id + status` (most common query pattern)
- `creator_id`
- `name` (for search/filtering)
- `status` (for global status filtering)

**Foreign Keys:**
- `organization_id` -> organizations.id (onDelete: cascade)
- `creator_id` -> users.id (onDelete: set null)

**Media Collections:** Preview images stored via Spatie Media Library (`preview_image` collection)

### 1.4 Create Campaigns Table

**File:** `database/migrations/YYYY_MM_DD_create_campaigns_table.php`

**Fields:**
- `id` - uuid primary
- `organization_id` - uuid, foreign -> organizations.id (cascade)
- `design_id` - uuid, foreign -> designs.id (restrict - prevent deletion if campaigns exist)
- `creator_id` - uuid, nullable, foreign -> users.id (set null)
- `name` - string, required
- `description` - text, nullable
- `variable_mapping` - jsonb (how CSV columns map to design variables)
- `status` - enum: draft, active, completed, cancelled (default: draft)
- `start_date` - date, nullable
- `end_date` - date, nullable
- `certificate_limit` - integer, nullable
- `certificates_issued` - integer, default 0
- `completed_at` - timestamp, nullable
- `completion_reason` - enum: limit_reached, date_reached, manual, nullable
- `timestamps`

**Indexes:**
- composite: `organization_id + status` (most common query pattern)
- composite: `organization_id + created_at` (for sorting campaigns)
- `design_id`
- `creator_id`
- `status` (for filtering all active/completed campaigns)
- `start_date`, `end_date` (for date range queries)

**Foreign Keys:**
- `organization_id` -> organizations.id (onDelete: cascade)
- `design_id` -> designs.id (onDelete: restrict - prevent design deletion if used)
- `creator_id` -> users.id (onDelete: set null)

### 1.5 Create Certificates Table

**File:** `database/migrations/YYYY_MM_DD_create_certificates_table.php`

**Fields:**
- `id` - uuid primary
- `organization_id` - uuid, foreign -> organizations.id (cascade)
- `design_id` - uuid, foreign -> designs.id (restrict - prevent deletion if certificates exist)
- `campaign_id` - uuid, nullable, foreign -> campaigns.id (set null - allow campaign deletion)
- `issued_to_user_id` - uuid, nullable, foreign -> users.id (set null - allow user deletion)
- `recipient_name` - string, required
- `recipient_email` - string, required
- `recipient_data` - jsonb (all variable values: {name: "Dylan", course: "Football"})
- `certificate_data` - jsonb (final rendered design with values - use jsonb for PostgreSQL)
- `verification_token` - string, unique, indexed (for public verification lookup)
- `status` - enum: pending, issued, expired, revoked (default: pending)
- `issued_at` - timestamp, nullable
- `expires_at` - timestamp, nullable (for expiry checks)
- `revoked_at` - timestamp, nullable
- `revocation_reason` - text, nullable
- `timestamps`

**Indexes:**
- composite: `organization_id + status` (most common query pattern)
- composite: `organization_id + issued_at` (for sorting certificates)
- `campaign_id` (for campaign certificate lists)
- `design_id` (for design usage stats)
- `recipient_email` (for finding certificates by email)
- `verification_token` (unique, for public verification)
- `issued_to_user_id` (for user's certificates)
- `status` (for filtering by status)
- `expires_at` (for expiry batch jobs)

**Foreign Keys:**
- `organization_id` -> organizations.id (onDelete: cascade)
- `design_id` -> designs.id (onDelete: restrict)
- `campaign_id` -> campaigns.id (onDelete: set null)
- `issued_to_user_id` -> users.id (onDelete: set null)

**Media Collections:** Certificate PDFs stored via Spatie Media Library (`certificate_pdf` collection)

### 1.6 Create Design Templates Table

**File:** `database/migrations/YYYY_MM_DD_create_design_templates_table.php`

**Fields:**
- `id` - uuid primary
- `name` - string, required
- `description` - text, nullable
- `template_data` - jsonb (Fabric.js canvas data - use jsonb for PostgreSQL)
- `variables` - jsonb (list of available variables)
- `category` - string, nullable (training, education, achievement)
- `is_active` - boolean, default true
- `is_public` - boolean, default true
- `usage_count` - integer, default 0
- `timestamps`

**Indexes:**
- composite: `is_active + is_public` (for public template browsing)
- `category` (for filtering by category)
- `is_active` (for admin template management)
- `usage_count` (for sorting by popularity)

**Foreign Keys:** None (admin-managed, not organization-scoped)

**Media Collections:** Preview images stored via Spatie Media Library (`preview_image` collection), NOT as database column

### 1.7 Update Users Table

**File:** `database/migrations/YYYY_MM_DD_add_profile_fields_to_users_table.php`

**Add fields:**
- `phone_number` - string, nullable
- `is_admin` - boolean, default false, indexed (for admin route filtering)
- `is_onboarded` - boolean, default false
- `onboarded_at` - timestamp, nullable
- `wants_organization` - boolean, default false
- `profile_completed` - boolean, default false

**Indexes:**
- `is_admin` (for admin middleware/route filtering)
- composite: `is_onboarded + created_at` (for onboarding stats)

**Note:** Users table already has Cashier columns (stripe_id, pm_type, pm_last_four, trial_ends_at) but Organizations will be billable. These user Cashier columns may be used for individual subscriptions in the future or can be removed if not needed.

### 1.8 Update Spatie Permissions Config

**File:** `config/permission.php`

**Changes:**
- Enable teams: `'teams' => true`
- Set team foreign key: `'team_foreign_key' => 'organization_id'`
- Configure model morphs to use UUIDs

## Phase 2: Enums

Create enums for type safety:

- `app/Enums/OrganizationStatus.php` - Active, Suspended
- `app/Enums/OrganizationUserStatus.php` - Active, Inactive, Pending
- `app/Enums/DesignStatus.php` - Draft, Active, Inactive, Archived
- `app/Enums/CampaignStatus.php` - Draft, Active, Completed, Cancelled
- `app/Enums/CampaignCompletionReason.php` - LimitReached, DateReached, Manual
- `app/Enums/CertificateStatus.php` - Pending, Issued, Expired, Revoked

## Phase 3: Models

### 3.1 Organization Model

**File:** `app/Models/Organization.php`

**Traits:** HasFactory, HasUuids, Billable (Cashier), InteractsWithMedia

**Fillable:** name, description, email, phone_number, website, status, settings

**Casts:** status (OrganizationStatus enum), settings (array)

**Relationships:**
- `users()` - belongsToMany(User) with organizationUser pivot
- `designs()` - hasMany(Design)
- `campaigns()` - hasMany(Campaign)
- `certificates()` - hasMany(Certificate)

**Methods:**
- `isActive()` - check if status is active
- `isSuspended()` - check if status is suspended
- `activeSubscription()` - get current subscription
- `hasActiveSubscription()` - boolean check
- `canCreateMoreCertificates()` - check subscription limits
- `registerMediaCollections()` - logo collection

**Scopes:**
- `scopeActive($query)` - filter active organizations

### 3.2 OrganizationUser Pivot Model

**File:** `app/Models/OrganizationUser.php`

**Extends:** Pivot

**Fillable:** organization_id, user_id, status, invited_at, accepted_at, invitation_token, invitation_expires_at, invited_role

**Casts:** status (enum), dates (datetime)

**Relationships:**
- `organization()` - belongsTo(Organization)
- `user()` - belongsTo(User)

**Methods:**
- `isActive()` - status is active
- `isPending()` - status is pending
- `isInvitationExpired()` - check expiration
- `acceptInvitation()` - accept and update status
- `generateInvitationToken()` - create unique token

### 3.3 User Model (Update Existing)

**File:** `app/Models/User.php`

**Add Traits:** HasRoles (Spatie), InteractsWithMedia

**Add Fillable:** phone_number, is_admin, is_onboarded, onboarded_at, wants_organization, profile_completed

**Add Casts:** is_admin, is_onboarded, wants_organization, profile_completed (boolean), onboarded_at (datetime)

**Add Relationships:**
- `organizationMemberships()` - hasMany(OrganizationUser)
- `organizations()` - belongsToMany(Organization) via organizationUser
- `createdDesigns()` - hasMany(Design, 'creator_id')
- `createdCampaigns()` - hasMany(Campaign, 'creator_id')
- `issuedCertificates()` - hasMany(Certificate, 'issued_to_user_id')

**Add Methods:**
- `isAdmin()` - check admin status
- `currentOrganization()` - get from session
- `switchOrganization($organizationId)` - update session
- `hasAccessToOrganization($organizationId)` - check membership
- `hasRoleInOrganization($role, $organizationId)` - scoped role check
- `canInOrganization($permission, $organizationId)` - scoped permission check
- `initials()` - get user initials
- `registerMediaCollections()` - avatar collection

### 3.4 Design Model

**File:** `app/Models/Design.php`

**Traits:** HasFactory, HasUuids, InteractsWithMedia

**Fillable:** organization_id, creator_id, name, description, design_data, variables, settings, status

**Casts:** design_data (array), variables (array), settings (array), status (enum)

**Relationships:**
- `organization()` - belongsTo(Organization)
- `creator()` - belongsTo(User, 'creator_id')
- `campaigns()` - hasMany(Campaign)
- `certificates()` - hasMany(Certificate)

**Methods:**
- `isActive()` - status is active
- `isDraft()` - status is draft
- `duplicate()` - create copy
- `registerMediaCollections()` - preview_image collection

**Scopes:**
- `scopeForOrganization($query, $orgId)` - filter by organization
- `scopeActive($query)` - only active designs

### 3.5 Campaign Model

**File:** `app/Models/Campaign.php`

**Traits:** HasFactory, HasUuids

**Fillable:** organization_id, design_id, creator_id, name, description, variable_mapping, status, start_date, end_date, certificate_limit, certificates_issued, completed_at, completion_reason

**Casts:** variable_mapping (array), status (enum), dates (date), completed_at (datetime), completion_reason (enum)

**Relationships:**
- `organization()` - belongsTo(Organization)
- `design()` - belongsTo(Design)
- `creator()` - belongsTo(User, 'creator_id')
- `certificates()` - hasMany(Certificate)

**Methods:**
- `isActive()` - status is active
- `isCompleted()` - status is completed
- `canIssueMore()` - check certificate limit
- `markAsCompleted($reason)` - complete campaign
- `incrementCertificatesIssued()` - update counter

**Scopes:**
- `scopeForOrganization($query, $orgId)` - filter by organization

### 3.6 Certificate Model

**File:** `app/Models/Certificate.php`

**Traits:** HasFactory, HasUuids, InteractsWithMedia

**Fillable:** organization_id, design_id, campaign_id, issued_to_user_id, recipient_name, recipient_email, recipient_data, certificate_data, verification_token, status, issued_at, expires_at, revoked_at, revocation_reason

**Casts:** recipient_data (array), certificate_data (array), status (enum), dates (datetime)

**Relationships:**
- `organization()` - belongsTo(Organization)
- `design()` - belongsTo(Design)
- `campaign()` - belongsTo(Campaign)
- `issuedToUser()` - belongsTo(User, 'issued_to_user_id')

**Methods:**
- `isIssued()` - status is issued
- `isRevoked()` - status is revoked
- `revoke($reason)` - revoke certificate
- `issue()` - mark as issued
- `generateVerificationToken()` - create unique token
- `getVerificationUrl()` - public verification URL
- `generatePDF()` - create PDF from certificate_data
- `registerMediaCollections()` - certificate_pdf collection

**Scopes:**
- `scopeForOrganization($query, $orgId)` - filter by organization

### 3.7 DesignTemplate Model

**File:** `app/Models/DesignTemplate.php`

**Traits:** HasFactory, HasUuids, InteractsWithMedia

**Fillable:** name, description, template_data, variables, category, is_active, is_public, usage_count

**Note:** preview_image is NOT a fillable field - it's handled via Media Library

**Casts:** template_data (array), variables (array), is_active, is_public (boolean)

**Methods:**
- `incrementUsage()` - increment usage_count
- `createDesignFromTemplate($organizationId, $userId)` - copy to new design
- `registerMediaCollections()` - preview collection

**Scopes:**
- `scopeActive($query)` - only active templates
- `scopePublic($query)` - only public templates

## Phase 4: Middleware & Traits

### 4.1 OrganizationContext Middleware

**File:** `app/Http/Middleware/OrganizationContext.php`

**Purpose:** Set organization context for multi-tenancy

**Logic:**
- Get organization_id from session
- If not set and user has organizations, set to first one
- Set Spatie permissions team ID: `setPermissionsTeamId($organizationId)`
- Share organization data with Inertia

### 4.2 EnsureUserHasOrganization Middleware

**File:** `app/Http/Middleware/EnsureUserHasOrganization.php`

**Purpose:** Redirect to organization creation if user has no organization

**Logic:**
- Check if user belongs to any organization
- If not and wants_organization, redirect to create organization
- If not and !wants_organization, redirect to onboarding

### 4.3 CheckSubscription Middleware

**File:** `app/Http/Middleware/CheckSubscription.php`

**Purpose:** Ensure organization has active subscription

**Logic:**
- Get current organization
- Check if has active subscription or trial
- If not, redirect to subscription/billing page
- Allow access to billing routes without subscription

### 4.4 AdminMiddleware

**File:** `app/Http/Middleware/AdminMiddleware.php`

**Purpose:** Restrict admin routes

**Logic:**
- Check if user is_admin
- If not, abort 403

### 4.5 Register Middleware

**File:** `bootstrap/app.php`

Register in web middleware group:
- `OrganizationContext` (for authenticated users)
- `EnsureUserHasOrganization` (for protected routes)
- `CheckSubscription` (for feature routes)

## Phase 5: Permissions & Roles

### 5.1 Permissions Seeder

**File:** `database/seeders/PermissionsSeeder.php`

**Permissions to create:**

**Organization:**
- view-organization
- update-organization
- delete-organization
- manage-billing

**Users:**
- view-users
- invite-users
- update-users
- remove-users

**Designs:**
- view-designs
- create-designs
- update-designs
- delete-designs
- publish-designs

**Campaigns:**
- view-campaigns
- create-campaigns
- update-campaigns
- delete-campaigns
- execute-campaigns

**Certificates:**
- view-certificates
- create-certificates
- update-certificates
- delete-certificates
- revoke-certificates
- download-certificates

**Roles to create (per organization):**

**Administrator:**
- All permissions

**Manager:**
- All except: delete-organization, manage-billing

**Designer:**
- view/create/update-designs
- view-campaigns
- view-certificates

**Member:**
- view-designs
- view-campaigns
- view-certificates

### 5.2 Policies

Create policies for authorization:

- `app/Policies/OrganizationPolicy.php` - view, update, delete, manageBilling, inviteUsers
- `app/Policies/DesignPolicy.php` - viewAny, view, create, update, delete, publish
- `app/Policies/CampaignPolicy.php` - viewAny, view, create, update, delete, execute
- `app/Policies/CertificatePolicy.php` - viewAny, view, create, update, delete, revoke, download
- `app/Policies/DesignTemplatePolicy.php` - viewAny, view (admin only for create/update/delete)

**Register policies in:** `app/Providers/AppServiceProvider.php` boot method

## Phase 6: Services

### 6.1 OrganizationService

**File:** `app/Services/OrganizationService.php`

**Methods:**
- `create($data, $userId)` - create organization and add creator as admin
- `inviteUser($organizationId, $email, $role)` - send invitation
- `acceptInvitation($token, $userId)` - accept invite
- `removeUser($organizationId, $userId)` - remove member
- `switchOrganization($userId, $organizationId)` - update session

### 6.2 DesignService

**File:** `app/Services/DesignService.php`

**Methods:**
- `create($organizationId, $userId, $data)` - create new design
- `duplicate($designId)` - copy design
- `createFromTemplate($templateId, $organizationId, $userId)` - create from template
- `updateDesignData($designId, $designData)` - update Fabric.js data

### 6.3 CampaignService

**File:** `app/Services/CampaignService.php`

**Methods:**
- `create($organizationId, $userId, $data)` - create campaign
- `execute($campaignId)` - start certificate generation
- `importRecipients($campaignId, $csvFile)` - bulk import from CSV
- `checkCompletion($campaignId)` - check if limits reached

### 6.4 CertificateService

**File:** `app/Services/CertificateService.php`

**Methods:**
- `create($campaignId, $recipientData)` - create single certificate
- `bulkCreate($campaignId, $recipientsArray)` - batch create
- `generatePDF($certificateId)` - render and store PDF
- `revoke($certificateId, $reason)` - revoke certificate
- `verify($verificationToken)` - verify certificate authenticity

### 6.5 SubscriptionService

**File:** `app/Services/SubscriptionService.php`

**Methods:**
- `createCheckoutSession($organizationId, $priceId)` - Stripe checkout
- `handleWebhook($payload)` - process Stripe webhooks
- `cancelSubscription($organizationId)` - cancel subscription
- `resumeSubscription($organizationId)` - resume cancelled subscription
- `updatePaymentMethod($organizationId, $paymentMethodId)` - update card

## Phase 7: Jobs

### 7.1 GenerateCertificatePDF

**File:** `app/Jobs/GenerateCertificatePDF.php`

**Purpose:** Async PDF generation using Fabric.js canvas to image/PDF

**Queue:** default

### 7.2 ProcessBulkCertificateImport

**File:** `app/Jobs/ProcessBulkCertificateImport.php`

**Purpose:** Process CSV import and create certificates in batches

**Queue:** default

### 7.3 SendCertificateEmail

**File:** `app/Jobs/SendCertificateEmail.php`

**Purpose:** Email certificate PDF to recipient

**Queue:** emails

### 7.4 CheckCampaignCompletion

**File:** `app/Jobs/CheckCampaignCompletion.php`

**Purpose:** Check if campaign should be completed (limit/date reached)

**Queue:** default

## Phase 8: Events & Listeners

### 8.1 Events

- `app/Events/OrganizationCreated.php` - new organization created
- `app/Events/UserInvitedToOrganization.php` - user invitation sent
- `app/Events/CertificateIssued.php` - certificate issued
- `app/Events/CampaignCompleted.php` - campaign completed

### 8.2 Listeners

- `app/Listeners/CreateStripeCustomer.php` - listen to OrganizationCreated
- `app/Listeners/SendOrganizationInvitation.php` - listen to UserInvitedToOrganization
- `app/Listeners/SendCertificateNotification.php` - listen to CertificateIssued
- `app/Listeners/NotifyCampaignCompletion.php` - listen to CampaignCompleted

**Register in:** `app/Providers/EventServiceProvider.php`

## Phase 9: Controllers

### 9.1 Organization Controllers

**DashboardController** - `app/Http/Controllers/DashboardController.php`
- `index()` - dashboard with stats

**OrganizationController** - `app/Http/Controllers/Organizations/OrganizationController.php`
- `index()` - list user's organizations
- `create()` - show create form
- `store()` - create organization
- `show($id)` - organization details
- `update($id)` - update organization
- `destroy($id)` - delete organization

**OrganizationUserController** - `app/Http/Controllers/Organizations/OrganizationUserController.php`
- `index()` - list organization members
- `invite()` - invite user
- `destroy($id)` - remove user

**OrganizationInvitationController** - `app/Http/Controllers/Organizations/OrganizationInvitationController.php`
- `show($token)` - show invitation
- `accept($token)` - accept invitation
- `decline($token)` - decline invitation

**OrganizationSwitchController** - `app/Http/Controllers/Organizations/OrganizationSwitchController.php`
- `store($id)` - switch to organization

**OrganizationSubscriptionController** - `app/Http/Controllers/Organizations/OrganizationSubscriptionController.php`
- `index()` - subscription details
- `checkout($priceId)` - create checkout session
- `success()` - subscription success page
- `cancel()` - subscription cancel page
- `portal()` - redirect to Stripe portal

### 9.2 Design Controllers

**DesignController** - `app/Http/Controllers/Designs/DesignController.php`
- `index()` - list designs with filters
- `create()` - show create form with templates
- `store()` - create design
- `show($id)` - design details
- `edit($id)` - design editor
- `update($id)` - update design
- `destroy($id)` - delete design
- `duplicate($id)` - duplicate design

**DesignTemplateController** - `app/Http/Controllers/Designs/DesignTemplateController.php`
- `index()` - list public templates
- `show($id)` - template preview
- `use($id)` - create design from template

### 9.3 Campaign Controllers

**CampaignController** - `app/Http/Controllers/Campaigns/CampaignController.php`
- `index()` - list campaigns
- `create()` - show create form
- `store()` - create campaign
- `show($id)` - campaign details and certificates
- `update($id)` - update campaign
- `destroy($id)` - delete campaign
- `execute($id)` - start campaign

**CampaignImportController** - `app/Http/Controllers/Campaigns/CampaignImportController.php`
- `create($campaignId)` - show import form
- `store($campaignId)` - upload CSV and queue job

### 9.4 Certificate Controllers

**CertificateController** - `app/Http/Controllers/Certificates/CertificateController.php`
- `index()` - list certificates with filters
- `create()` - create single certificate form
- `store()` - create certificate
- `show($id)` - certificate details
- `download($id)` - download PDF
- `revoke($id)` - revoke certificate

**CertificateVerificationController** - `app/Http/Controllers/CertificateVerificationController.php` (public)
- `show($token)` - verify and display certificate

### 9.5 Settings Controllers

**ProfileController** - `app/Http/Controllers/Settings/ProfileController.php`
- `edit()` - show profile form
- `update()` - update profile

**PasswordController** - `app/Http/Controllers/Settings/PasswordController.php`
- `edit()` - show password form
- `update()` - update password

**AppearanceController** - `app/Http/Controllers/Settings/AppearanceController.php`
- `edit()` - show appearance settings
- `update()` - update theme/preferences

### 9.6 Admin Controllers

**Admin\DashboardController** - `app/Http/Controllers/Admin/DashboardController.php`
- `index()` - admin dashboard with stats

**Admin\OrganizationController** - `app/Http/Controllers/Admin/OrganizationController.php`
- Full CRUD for organizations

**Admin\UserController** - `app/Http/Controllers/Admin/UserController.php`
- Full CRUD for users

**Admin\DesignTemplateController** - `app/Http/Controllers/Admin/DesignTemplateController.php`
- Full CRUD for design templates

**Admin\SettingsController** - `app/Http/Controllers/Admin/SettingsController.php`
- Application settings management

## Phase 10: Form Requests

Create form requests for validation:

- `app/Http/Requests/StoreOrganizationRequest.php`
- `app/Http/Requests/UpdateOrganizationRequest.php`
- `app/Http/Requests/InviteUserRequest.php`
- `app/Http/Requests/StoreDesignRequest.php`
- `app/Http/Requests/UpdateDesignRequest.php`
- `app/Http/Requests/StoreCampaignRequest.php`
- `app/Http/Requests/UpdateCampaignRequest.php`
- `app/Http/Requests/StoreCertificateRequest.php`
- `app/Http/Requests/ImportCertificatesRequest.php`
- `app/Http/Requests/UpdateProfileRequest.php`
- `app/Http/Requests/StoreDesignTemplateRequest.php`

## Phase 11: Routes

### 11.1 Web Routes

**File:** `routes/web.php`

**Public routes:**
- `GET /` - home/landing page
- `GET /verify/{token}` - certificate verification

**Auth routes** (Fortify):
- Login, register, password reset (already configured)

**Protected routes** (auth, verified middleware):

**Dashboard:**
- `GET /dashboard` - dashboard

**Organizations:**
- Resource routes for organizations
- `GET /organizations/{id}/switch` - switch organization
- `GET /organizations/invitations/{token}` - view invitation
- `POST /organizations/invitations/{token}/accept` - accept
- `POST /organizations/invitations/{token}/decline` - decline

**Designs:**
- Resource routes for designs
- `POST /designs/{id}/duplicate` - duplicate design
- `GET /design-templates` - browse templates
- `POST /design-templates/{id}/use` - use template

**Campaigns:**
- Resource routes for campaigns
- `POST /campaigns/{id}/execute` - start campaign
- `GET /campaigns/{id}/import` - import form
- `POST /campaigns/{id}/import` - upload CSV

**Certificates:**
- Resource routes for certificates
- `GET /certificates/{id}/download` - download PDF
- `POST /certificates/{id}/revoke` - revoke

**Settings:**
- `GET /settings/profile` - profile settings
- `GET /settings/password` - password settings
- `GET /settings/appearance` - appearance settings

**Subscription:**
- `GET /subscription` - view subscription
- `POST /subscription/checkout/{priceId}` - checkout
- `GET /subscription/success` - success page
- `GET /subscription/cancel` - cancel page
- `GET /subscription/portal` - billing portal

### 11.2 Admin Routes

**File:** `routes/admin.php` (create new file)

**Prefix:** `/admin`

**Middleware:** auth, verified, admin

**Routes:**
- Dashboard
- Organizations CRUD
- Users CRUD
- Design Templates CRUD
- Settings

**Include in web.php:** `require __DIR__.'/admin.php';`

### 11.3 API Routes (Optional)

**File:** `routes/api.php`

API routes for mobile apps or external integrations (future phase)

## Phase 12: Vue.js Components & Pages

### 12.1 Layouts

**AppLayout.vue** - `resources/js/layouts/AppLayout.vue`
- Main navigation with organization switcher
- Sidebar with feature navigation
- User menu
- Breadcrumbs

**AuthLayout.vue** - `resources/js/layouts/AuthLayout.vue`
- Minimal layout for auth pages

**AdminLayout.vue** - `resources/js/layouts/AdminLayout.vue`
- Admin-specific navigation and sidebar

### 12.2 Shared Components

**Core Components:**
- `Button.vue` - reusable button
- `Input.vue` - form input
- `Select.vue` - dropdown
- `Textarea.vue` - text area
- `Checkbox.vue` - checkbox
- `Modal.vue` - modal dialog
- `Alert.vue` - alert notifications
- `EmptyState.vue` - empty state message
- `LoadingSpinner.vue` - loading indicator

**Feature Components:**
- `OrganizationSwitcher.vue` - dropdown to switch organizations
- `DesignCanvas.vue` - Fabric.js canvas editor
- `DesignVariablesList.vue` - list and manage design variables
- `TemplateCard.vue` - template preview card
- `CertificatePreview.vue` - certificate preview
- `FileUpload.vue` - drag-drop file upload
- `CSVImporter.vue` - CSV import with mapping
- `DataTable.vue` - data table with sorting/filtering

### 12.3 Composables

**Organization:**
- `useOrganization.ts` - current organization state and switching
- `usePermissions.ts` - permission checking in Vue

**Features:**
- `useDesignEditor.ts` - Fabric.js integration
- `useMediaUpload.ts` - file upload with Spatie Media Library
- `useCertificateGenerator.ts` - certificate generation logic

### 12.4 Page Components

**Dashboard:**
- `Dashboard.vue` - stats, recent certificates, quick actions

**Organizations:**
- `Organizations/Index.vue` - list organizations
- `Organizations/Create.vue` - create form
- `Organizations/Show.vue` - organization details tabs (info, users, subscription)
- `Organizations/InvitationAccept.vue` - accept invitation page

**Designs:**
- `Designs/Index.vue` - designs list with filters
- `Designs/Create.vue` - choose template or blank
- `Designs/Editor.vue` - Fabric.js design editor
- `Designs/Show.vue` - design preview

**Campaigns:**
- `Campaigns/Index.vue` - campaigns list
- `Campaigns/Create.vue` - create campaign form
- `Campaigns/Show.vue` - campaign details with certificates table
- `Campaigns/Import.vue` - CSV import wizard

**Certificates:**
- `Certificates/Index.vue` - certificates list with filters
- `Certificates/Create.vue` - create single certificate
- `Certificates/Show.vue` - certificate details and PDF viewer
- `Certificates/Verify.vue` - public verification page

**Settings:**
- `Settings/Profile.vue` - profile form
- `Settings/Password.vue` - password change form
- `Settings/Appearance.vue` - theme settings

**Subscription:**
- `Subscription/Index.vue` - current plan and billing
- `Subscription/Checkout.vue` - plan selection and checkout
- `Subscription/Success.vue` - success message
- `Subscription/Cancel.vue` - cancellation message

**Admin:**
- `Admin/Dashboard.vue` - admin stats
- `Admin/Organizations/Index.vue` - all organizations
- `Admin/Users/Index.vue` - all users
- `Admin/DesignTemplates/Index.vue` - manage templates
- `Admin/DesignTemplates/Editor.vue` - template editor

**Auth:**
- Already exist: Login, Register, ForgotPassword (from Fortify)

## Phase 13: Inertia Shared Data

**File:** `app/Http/Middleware/HandleInertiaRequests.php`

**Share:**
- `auth.user` - authenticated user with organizations
- `currentOrganization` - current organization details
- `organizations` - list of user's organizations
- `permissions` - current organization permissions
- `flash` - flash messages
- `appName` - application name
- `subscription` - current organization subscription status

## Phase 14: Seeders & Factories

### 14.1 Seeders

**DatabaseSeeder.php** - orchestrate all seeders

**PermissionsSeeder.php** - seed permissions and roles

**DemoSeeder.php** - create demo data:
- Admin user
- Sample organization
- Sample designs
- Sample campaigns
- Sample certificates
- Design templates

### 14.2 Factories

- `UserFactory.php` (update existing)
- `OrganizationFactory.php`
- `DesignFactory.php`
- `CampaignFactory.php`
- `CertificateFactory.php`
- `DesignTemplateFactory.php`
- `OrganizationUserFactory.php`

## Phase 15: Testing

### 15.1 Feature Tests

**Organization Tests:**
- `tests/Feature/Organizations/OrganizationTest.php` - CRUD operations
- `tests/Feature/Organizations/InvitationTest.php` - invite/accept flow
- `tests/Feature/Organizations/SwitchTest.php` - organization switching

**Design Tests:**
- `tests/Feature/Designs/DesignTest.php` - CRUD operations
- `tests/Feature/Designs/TemplateTest.php` - template usage

**Campaign Tests:**
- `tests/Feature/Campaigns/CampaignTest.php` - CRUD operations
- `tests/Feature/Campaigns/ImportTest.php` - CSV import

**Certificate Tests:**
- `tests/Feature/Certificates/CertificateTest.php` - CRUD operations
- `tests/Feature/Certificates/VerificationTest.php` - public verification
- `tests/Feature/Certificates/RevocationTest.php` - revoke flow

**Subscription Tests:**
- `tests/Feature/Subscriptions/SubscriptionTest.php` - Stripe integration
- `tests/Feature/Subscriptions/CheckoutTest.php` - checkout flow

**Permission Tests:**
- `tests/Feature/Permissions/PermissionTest.php` - role-based access

### 15.2 Unit Tests

- `tests/Unit/Models/OrganizationTest.php` - model methods
- `tests/Unit/Models/CertificateTest.php` - verification token generation
- `tests/Unit/Services/CertificateServiceTest.php` - service logic

## Phase 16: Configuration & Environment

### 16.1 Environment Variables

Add to `.env.example`:

```
# Stripe
STRIPE_KEY=
STRIPE_SECRET=
STRIPE_WEBHOOK_SECRET=

# Certificate Settings
CERTIFICATE_EXPIRY_DAYS=365
CERTIFICATE_VERIFICATION_URL=

# Organization Settings
DEFAULT_TRIAL_DAYS=14
MAX_FREE_CERTIFICATES=10

# File Storage
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=
AWS_BUCKET=
```

### 16.2 Config Files

**config/certificates.php** (new):
- Default expiry days
- Supported formats
- PDF settings
- Verification URL

**config/subscriptions.php** (new):
- Stripe price IDs
- Feature limits per plan
- Trial settings

## Phase 17: Additional Features

### 17.1 Notifications

**Mail notifications:**
- `OrganizationInvitationMail` - invite user to organization
- `CertificateIssuedMail` - send certificate to recipient
- `CampaignCompletedMail` - notify campaign creator

**Database notifications:**
- Certificate issued
- Campaign completed
- Subscription expiring

### 17.2 Observers

**OrganizationObserver** - `app/Observers/OrganizationObserver.php`
- created: create Stripe customer
- updated: sync to Stripe
- deleted: cancel subscriptions

**CertificateObserver** - `app/Observers/CertificateObserver.php`
- creating: generate verification token
- created: dispatch GenerateCertificatePDF job

**CampaignObserver** - `app/Observers/CampaignObserver.php`
- updated: check if completed

**Register in:** `app/Providers/AppServiceProvider.php`

### 17.3 Commands

**Console commands:**

- `app/Console/Commands/CheckExpiredCertificates.php` - mark expired
- `app/Console/Commands/SyncStripeData.php` - sync organizations to Stripe
- `app/Console/Commands/GenerateMissingPDFs.php` - regenerate failed PDFs

**Schedule in:** `routes/console.php`

## Implementation Priority

### Priority 1 - Core Foundation (Start Here)
1. Database migrations (organizations, organization_user, designs, campaigns, certificates, design_templates)
2. Enums
3. Models (Organization, OrganizationUser, Design, Campaign, Certificate, DesignTemplate)
4. Update User model
5. Configure Spatie Permissions for teams
6. Permissions seeder

### Priority 2 - Multi-tenancy & Auth
1. OrganizationContext middleware
2. EnsureUserHasOrganization middleware
3. OrganizationService
4. Organization controllers
5. Organization Vue pages
6. Organization switcher component

### Priority 3 - Core Features
1. DesignService and controllers
2. Design Vue pages and editor component
3. CampaignService and controllers
4. Campaign Vue pages
5. CertificateService and controllers
6. Certificate Vue pages
7. PDF generation job

### Priority 4 - Subscriptions
1. CheckSubscription middleware
2. SubscriptionService
3. Subscription controllers
4. Subscription Vue pages
5. Stripe webhook handling

### Priority 5 - Admin & Templates
1. AdminMiddleware
2. Admin controllers
3. Admin Vue pages
4. Design template CRUD
5. Template editor

### Priority 6 - Polish & Testing
1. Notifications
2. Observers
3. Commands
4. Feature tests
5. Unit tests
6. Browser tests (Pest v4)

## Notes & Conventions

**UUIDs:**
- All primary keys use UUIDs via `HasUuids` trait
- Foreign keys are uuid type in migrations

**Multi-tenancy:**
- Organization context stored in session
- Spatie permissions use organization_id as team_foreign_key
- Always call `setPermissionsTeamId($organizationId)` before permission checks
- Use policies for authorization, not direct permission checks in controllers

**Media Library:**
- **NO database columns for media files** - all media stored via Spatie Media Library
- Store certificate PDFs in `certificate_pdf` collection (Certificate model)
- Store design previews in `preview_image` collection (Design model)
- Store organization logos in `logo` collection (Organization model)
- Store design template previews in `preview_image` collection (DesignTemplate model)
- Store user avatars in `avatar` collection (User model)
- Use temporary signed URLs for private files (S3)
- Register media collections in model's `registerMediaCollections()` method

**Subscriptions:**
- Organizations are billable (not users)
- Use Laravel Cashier for Stripe integration
- Check subscription before accessing premium features
- Handle Stripe webhooks for subscription updates

**Fabric.js Integration:**
- Store design_data as JSON (Fabric.js canvas.toJSON())
- Use Fabric.js for frontend design editor
- Generate PDFs from canvas using canvas.toDataURL() or server-side rendering

**Queue Jobs:**
- PDF generation should be queued
- Bulk imports should be queued in batches
- Email sending should be queued

**Testing:**
- Use Pest for all tests
- Feature tests should cover happy paths and authorization
- Use factories for test data
- Mock Stripe in tests

