# Laravel Inertia + Vue.js Project Setup

## Project Overview

Build a Laravel 12 application with Inertia.js and Vue.js frontend for certificate management system with multi-tenant organization support.

**Core Requirements:**

- Laravel 12 with Inertia.js and Vue.js 3
- Spatie Permissions with teams feature (organizations)
- Spatie Media Library for file uploads
- UUIDs for all primary keys
- Multi-tenant organizations system
- Laravel Cashier for subscriptions
- Tailwind CSS 4 for styling

## Phase 1: Project Initialization & Core Setup

### 1.1 Laravel Project Setup

- Create new Laravel 12 project
- Configure `.env` with database credentials
- Set application key
- Configure session driver (database/file)
- Configure cache driver
- Setup queue configuration (database/redis)

### 1.2 Install Core Dependencies

**Composer packages:**

- `inertiajs/inertia-laravel` - Inertia.js Laravel adapter
- `spatie/laravel-permission` - Permissions with teams support
- `spatie/laravel-medialibrary` - Media library for file management
- `laravel/cashier` - Stripe subscription management
- `laravel/sanctum` - API authentication (if needed)
- `laravel/horizon` - Queue monitoring (optional)

**NPM packages:**

- `@inertiajs/vue3` - Inertia.js Vue adapter
- `@inertiajs/progress` - Progress bar for Inertia requests
- `vue@^3` - Vue.js framework
- `@vueuse/core` - Vue composition utilities
- `axios` - HTTP client (already in Laravel)
- `@tailwindcss/vite` - Tailwind CSS 4
- `tailwindcss@^4` - Tailwind CSS
- `autoprefixer` - CSS autoprefixer

### 1.3 Configure Inertia.js

- Create `app/Http/Middleware/HandleInertiaRequests.php`
- Register middleware in `bootstrap/app.php` web middleware group
- Configure shared data: user, organization context, flash messages, permissions
- Setup root template: `resources/views/app.blade.php` with Inertia root div

### 1.4 Configure Vue.js & Vite

- Update `vite.config.js` with Vue plugin
- Create `resources/js/app.js` - Initialize Inertia with Vue 3
- Create `resources/js/app.vue` - Root Vue component
- Configure Tailwind CSS in Vite
- Setup Hot Module Replacement (HMR)

### 1.5 Configure Spatie Permissions

- Publish Spatie Permissions config: `php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"`
- Configure `config/permission.php`:
- Enable teams feature: `'teams_by_guard' => ['web' => true]`
- Set team foreign key: `'team_foreign_key' => 'organization_id'`
- Register team resolver if custom logic needed

### 1.6 Configure Spatie Media Library

- Publish Media Library config: `php artisan vendor:publish --provider="Spatie\MediaLibrary\MediaLibraryServiceProvider"`
- Configure `config/media-library.php`:
- Set disk for media storage (s3/local)
- Configure conversions (thumbnails, resizing)
- Setup temporary signed URLs for private storage

## Phase 2: Database Structure

### 2.1 Create Users Table Migration

- `database/migrations/YYYY_MM_DD_create_users_table.php`
- Fields: uuid id (primary), name, email (unique), password, phone_number, email_verified_at, is_onboarded, onboarded_at, wants_organization, profile_completed, is_admin, remember_token, timestamps
- Indexes: email, is_onboarded + created_at

### 2.2 Create Organizations Table Migration

- `database/migrations/YYYY_MM_DD_create_organizations_table.php`
- Fields: uuid id (primary), name, description, email, phone_number, website, street, house_number, postal_code, province, city, country (default NL), tax_id, coc_number, currency (default EUR), timezone (default Europe/Amsterdam), locale (default nl), settings (json), status (enum: active/suspended), email_verified_at, phone_verified_at, notification settings (email_notifications, certificate_expiry_notifications, design_approval_notifications, weekly_reports), notification_email, stripe_id, pm_type, pm_last_four, trial_ends_at, timestamps
- Indexes: status + created_at, email, tax_id
- Foreign keys: none (root table)

### 2.3 Create Organization User Pivot Table Migration

- `database/migrations/YYYY_MM_DD_create_organization_user_table.php`
- Fields: id (auto-increment), uuid organization_id, uuid user_id, status (enum: active/inactive/pending), invited_at, accepted_at, invitation_token, invited_role, invitation_expires_at, timestamps
- Indexes: unique organization_id + user_id, user_id + status, status, invitation_token
- Foreign keys: organization_id -> organizations.id (cascade), user_id -> users.id (cascade)

### 2.4 Create Spatie Permission Tables Migration

- Run migration: `php artisan migrate` (includes permission tables with team support)
- Tables: permissions, roles, model_has_permissions, model_has_roles, role_has_permissions
- All tables include `organization_id` column (team_foreign_key)

### 2.5 Create Media Table Migration

- Run migration: `php artisan migrate` (includes media table from Spatie Media Library)
- Table: media (handles file uploads, conversions, collections)

### 2.6 Create Designs Table Migration

- `database/migrations/YYYY_MM_DD_create_designs_table.php`
- Fields: uuid id (primary), uuid creator_id (nullable), uuid organization_id, name, description, design_data (jsonb), variables (jsonb), settings (json), status (enum: draft/active/inactive/archived), timestamps
- Indexes: organization_id + status, name
- Foreign keys: organization_id -> organizations.id (cascade), creator_id -> users.id (set null)

### 2.7 Create Campaigns Table Migration

- `database/migrations/YYYY_MM_DD_create_campaigns_table.php`
- Fields: uuid id (primary), uuid creator_id, uuid organization_id, uuid design_id, name, description, variable_mapping (json), status (enum), start_date, end_date, certificate_limit (integer), completed_at, completion_reason (enum), timestamps
- Indexes: organization_id + status, creator_id
- Foreign keys: creator_id -> users.id, organization_id -> organizations.id (cascade), design_id -> designs.id

### 2.8 Create Certificates Table Migration

- `database/migrations/YYYY_MM_DD_create_certificates_table.php`
- Fields: uuid id (primary), uuid campaign_id (nullable), uuid design_id, uuid organization_id, uuid issued_to_user_id (nullable), recipient_name, recipient_email, recipient_data (json), certificate_data (jsonb), status (enum), issued_at, expires_at, revoked_at, revocation_reason, timestamps
- Indexes: organization_id + status, campaign_id, issued_to_user_id, recipient_email
- Foreign keys: campaign_id -> campaigns.id (set null), design_id -> designs.id, organization_id -> organizations.id (cascade), issued_to_user_id -> users.id (set null)

### 2.9 Create Design Templates Table Migration

- `database/migrations/YYYY_MM_DD_create_design_templates_table.php`
- Fields: uuid id (primary), name, description, template_data (jsonb), preview_image, is_active, is_public, timestamps
- Indexes: is_active, is_public

### 2.10 Create Blog Tables Migrations

- `database/migrations/YYYY_MM_DD_create_blog_categories_table.php` - uuid id, name, slug, description, timestamps
- `database/migrations/YYYY_MM_DD_create_blog_tags_table.php` - uuid id, name, slug, timestamps
- `database/migrations/YYYY_MM_DD_create_blog_posts_table.php` - uuid id, title, slug, excerpt, content, cover_image, is_published, published_at, author_id, author_name, author_bio, author_avatar, seo_meta (json), reading_time, category_id, timestamps
- `database/migrations/YYYY_MM_DD_create_blog_post_tag_table.php` - blog_post_id, blog_tag_id, timestamps

### 2.11 Create Cashier Tables Migrations

- Run Cashier migrations: `php artisan cashier:install`
- Tables: subscriptions, subscription_items (managed by Cashier)

### 2.12 Create Additional Tables

- `database/migrations/YYYY_MM_DD_create_tags_table.php` - uuid id, name, slug, timestamps
- `database/migrations/YYYY_MM_DD_create_design_template_tags_table.php` - design_template_id, tag_id, timestamps
- `database/migrations/YYYY_MM_DD_create_cache_table.php` - Laravel cache table
- `database/migrations/YYYY_MM_DD_create_jobs_table.php` - Laravel jobs table
- `database/migrations/YYYY_MM_DD_create_notifications_table.php` - Laravel notifications table

## Phase 3: Models & Relationships

### 3.1 User Model

- File: `app/Models/User.php`
- Traits: HasFactory, HasRoles (Spatie), HasUuids, Impersonate (if needed), InteractsWithMedia (Spatie), Notifiable
- Fillable: name, email, password, phone_number, wants_organization, profile_completed, is_onboarded, onboarded_at
- Casts: email_verified_at (datetime), password (hashed), is_onboarded (boolean), onboarded_at (datetime), wants_organization (boolean), profile_completed (boolean)
- Relationships: organizationMemberships (HasMany OrganizationUser), organizations (BelongsToMany), createdCampaigns (HasMany), createdDesigns (HasMany)
- Methods: initials(), belongsToOrganization(), hasRoleInOrganization(), hasPermissionInOrganization(), getCurrentOrganization(), canInCurrentOrganization(), hasRoleInCurrentOrganization(), registerMediaCollections(), registerMediaConversions(), getAvatarUrl(), hasAvatar(), hasCompletedOnboarding(), markAsOnboarded(), needsOnboarding(), hasCompleteProfile(), wantsOrganization()

### 3.2 Organization Model

- File: `app/Models/Organization.php`
- Traits: Billable (Cashier), HasFactory, HasOrganizationScope, HasUuids
- Fillable: name, description, email, phone_number, website, address fields, tax_id, coc_number, currency, timezone, locale, settings, status, notification fields, stripe fields
- Casts: settings (array), email_verified_at (datetime), phone_verified_at (datetime), status (enum), notification booleans
- Relationships: organizationMemberships (HasMany OrganizationUser), users (BelongsToMany), subscriptions (HasMany), activeSubscription()
- Methods: isActive(), isSuspended(), current() (static), stripeName(), createAsStripeCustomer(), buildStripeAddress(), hasActiveSubscription()

### 3.3 OrganizationUser Pivot Model

- File: `app/Models/OrganizationUser.php`
- Extends: Pivot
- Fillable: organization_id, user_id, status, invited_at, accepted_at, invitation_token, invitation_expires_at, invited_role
- Casts: status (enum), dates for timestamps
- Relationships: organization (BelongsTo), user (BelongsTo)
- Methods: isActive(), isPending(), isInactive(), isInvitationExpired(), acceptInvitation(), getInvitedRole()

### 3.4 Design Model

- File: `app/Models/Design.php`
- Traits: HasFactory, HasOrganizationScope, HasUuids, InteractsWithMedia
- Fillable: name, description, design_data, variables, organization_id, creator_id, settings, status
- Casts: design_data (array), variables (array), settings (array), status (enum)
- Relationships: organization (BelongsTo), creator (BelongsTo), certificates (HasMany), campaigns (HasMany)
- Methods: isActive(), registerMediaCollections()

### 3.5 Campaign Model

- File: `app/Models/Campaign.php`
- Traits: HasFactory, HasOrganizationScope, HasUuids
- Fillable: creator_id, organization_id, design_id, name, description, variable_mapping, status, dates, certificate_limit, completed_at, completion_reason
- Casts: variable_mapping (array), status (enum), dates (datetime), completion_reason (enum)
- Relationships: creator (BelongsTo), organization (BelongsTo), design (BelongsTo), certificates (HasMany)
- Methods: status checks, completion methods

### 3.6 Certificate Model

- File: `app/Models/Certificate.php`
- Traits: HasFactory, HasOrganizationScope, HasUuids, InteractsWithMedia
- Fillable: campaign_id, design_id, organization_id, issued_to_user_id, recipient fields, certificate_data, status, dates, revocation fields
- Casts: recipient_data (array), certificate_data (array), status (enum), dates (datetime)
- Relationships: campaign (BelongsTo), design (BelongsTo), organization (BelongsTo), issuedToUser (BelongsTo), media collections
- Methods: status checks, revocation methods, PDF generation

### 3.7 DesignTemplate Model

- File: `app/Models/DesignTemplate.php`
- Traits: HasFactory, HasUuids, InteractsWithMedia
- Fillable: name, description, template_data, preview_image, is_active, is_public
- Casts: template_data (array), is_active (boolean), is_public (boolean)
- Relationships: tags (BelongsToMany)

### 3.8 Blog Models

- `app/Models/BlogCategory.php` - HasFactory, name, slug, description
- `app/Models/BlogTag.php` - HasFactory, name, slug
- `app/Models/BlogPost.php` - HasFactory, UUIDs, fillable fields, relationships to category, author, tags

### 3.9 Enums

- `app/Enums/OrganizationStatus.php` - ACTIVE, SUSPENDED
- `app/Enums/OrganizationUserStatus.php` - ACTIVE, INACTIVE, PENDING
- `app/Enums/DesignStatus.php` - DRAFT, ACTIVE, INACTIVE, ARCHIVED
- `app/Enums/CampaignStatus.php` - DRAFT, ACTIVE, COMPLETED, CANCELLED
- `app/Enums/CampaignCompletionReason.php` - LIMIT_REACHED, DATE_REACHED, MANUAL
- `app/Enums/CertificateStatus.php` - PENDING, ISSUED, EXPIRED, REVOKED

## Phase 4: Traits & Middleware

### 4.1 HasOrganizationScope Trait

- File: `app/Traits/HasOrganizationScope.php`
- Methods: scopeForCurrentOrganization() - filter by session organization_id, scopeForOrganization() - filter by specific organization_id

### 4.2 Middleware

- `app/Http/Middleware/OrganizationMiddleware.php` - Set permissions team ID from session organization_id
- `app/Http/Middleware/SubscriptionMiddleware.php` - Check organization has active subscription
- `app/Http/Middleware/AdminMiddleware.php` - Check user is admin
- Register middleware in `bootstrap/app.php`

## Phase 5: Policies & Permissions

### 5.1 Seed Permissions

- File: `database/seeders/RolesAndPermissionsSeeder.php`
- Permissions: view/update/delete organization, view/create/update/delete/invite organization users, view/create/update/delete/publish designs, view/create/update/delete/execute campaigns, view/create/update/delete/revoke/download certificates, view/update addresses, view/create/update/delete api tokens
- Roles: Administrator (all permissions), Manager (limited), Member (view only)
- Assign roles to users in organizations (team-scoped)

### 5.2 Create Policies

- `app/Policies/OrganizationPolicy.php` - viewAny, view, create, update, delete, manageUsers
- `app/Policies/DesignPolicy.php` - CRUD operations
- `app/Policies/CampaignPolicy.php` - CRUD operations
- `app/Policies/CertificatePolicy.php` - CRUD operations
- `app/Policies/DesignTemplatePolicy.php` - CRUD operations
- `app/Policies/RolePolicy.php` - manage roles
- `app/Policies/PermissionPolicy.php` - manage permissions
- Register policies in `app/Providers/AuthServiceProvider.php`

### 5.3 Permission Checking

- Use `setPermissionsTeamId($organizationId)` in middleware before permission checks
- Use `$user->can('permission')` in controllers/policies
- Check `$user->hasRole('role')` for role-based access
- Share permissions in Inertia middleware for frontend checks

## Phase 6: Vue.js Application Structure

### 6.1 Layout Components

- `resources/js/Layouts/AppLayout.vue` - Main app layout with navigation, sidebar, header
- `resources/js/Layouts/AuthLayout.vue` - Authentication pages layout
- `resources/js/Layouts/AdminLayout.vue` - Admin panel layout

### 6.2 Shared Components

- `resources/js/Components/Button.vue` - Reusable button component
- `resources/js/Components/Input.vue` - Form input component
- `resources/js/Components/Textarea.vue` - Textarea component
- `resources/js/Components/Select.vue` - Select dropdown component
- `resources/js/Components/Checkbox.vue` - Checkbox component
- `resources/js/Components/Radio.vue` - Radio button component
- `resources/js/Components/Modal.vue` - Modal dialog component
- `resources/js/Components/Table.vue` - Data table component
- `resources/js/Components/Form.vue` - Form wrapper component
- `resources/js/Components/Alert.vue` - Alert/notification component
- `resources/js/Components/FileUpload.vue` - File upload with progress
- `resources/js/Components/MediaLibrary.vue` - Spatie Media Library Vue component
- `resources/js/Components/Navigation.vue` - Main navigation menu
- `resources/js/Components/Sidebar.vue` - Sidebar navigation
- `resources/js/Components/UserMenu.vue` - User dropdown menu
- `resources/js/Components/OrganizationSelector.vue` - Organization switcher

### 6.3 Composables

- `resources/js/Composables/useOrganization.js` - Organization context management, switching
- `resources/js/Composables/usePermissions.js` - Permission checking utilities for Vue
- `resources/js/Composables/useMedia.js` - Media Library integration, uploads, temporary URLs
- `resources/js/Composables/useForm.js` - Form handling with Inertia form helper
- `resources/js/Composables/useFlash.js` - Flash message handling

### 6.4 Page Components Structure

- `resources/js/Pages/Auth/Login.vue`
- `resources/js/Pages/Auth/Register.vue`
- `resources/js/Pages/Auth/ForgotPassword.vue`
- `resources/js/Pages/Auth/ResetPassword.vue`
- `resources/js/Pages/Auth/VerifyEmail.vue`
- `resources/js/Pages/Dashboard.vue`
- `resources/js/Pages/Organizations/Information.vue`
- `resources/js/Pages/Organizations/Users.vue`
- `resources/js/Pages/Organizations/Address.vue`
- `resources/js/Pages/Organizations/Notifications.vue`
- `resources/js/Pages/Organizations/Subscription.vue`
- `resources/js/Pages/Designs/Index.vue`
- `resources/js/Pages/Designs/Create.vue`
- `resources/js/Pages/Designs/Edit.vue`
- `resources/js/Pages/Designs/Show.vue`
- `resources/js/Pages/Campaigns/Index.vue`
- `resources/js/Pages/Campaigns/Create.vue`
- `resources/js/Pages/Campaigns/Edit.vue`
- `resources/js/Pages/Campaigns/View.vue`
- `resources/js/Pages/Certificates/Index.vue`
- `resources/js/Pages/Certificates/Create.vue`
- `resources/js/Pages/Certificates/Edit.vue`
- `resources/js/Pages/Certificates/View.vue`
- `resources/js/Pages/Certificates/BulkImport.vue`
- `resources/js/Pages/Blogs/Index.vue`
- `resources/js/Pages/Blogs/Show.vue`
- `resources/js/Pages/Onboarding/Welcome.vue`
- `resources/js/Pages/Onboarding/EmailVerification.vue`
- `resources/js/Pages/Onboarding/ProfileCompletion.vue`
- `resources/js/Pages/Onboarding/PlanSelection.vue`
- `resources/js/Pages/Onboarding/OrganizationSetup.vue`
- `resources/js/Pages/Settings/Profile.vue`
- `resources/js/Pages/Settings/Password.vue`
- `resources/js/Pages/Settings/Appearance.vue`
- `resources/js/Pages/Admin/Dashboard.vue`
- `resources/js/Pages/Admin/Users/Index.vue`
- `resources/js/Pages/Admin/Users/Create.vue`
- `resources/js/Pages/Admin/Users/Edit.vue`
- `resources/js/Pages/Admin/Users/Show.vue`
- `resources/js/Pages/Admin/Organizations/Index.vue`
- `resources/js/Pages/Admin/Organizations/Create.vue`
- `resources/js/Pages/Admin/Organizations/Edit.vue`
- `resources/js/Pages/Admin/Organizations/Show.vue`
- `resources/js/Pages/Admin/DesignTemplates/Index.vue`
- `resources/js/Pages/Admin/DesignTemplates/Create.vue`
- `resources/js/Pages/Admin/DesignTemplates/Edit.vue`
- `resources/js/Pages/Admin/DesignTemplates/Show.vue`
- `resources/js/Pages/Admin/Roles/Index.vue`
- `resources/js/Pages/Admin/Roles/Create.vue`
- `resources/js/Pages/Admin/Roles/Edit.vue`

## Phase 7: Controllers

### 7.1 Auth Controllers

- `app/Http/Controllers/Auth/LoginController.php` - handle login, return Inertia response
- `app/Http/Controllers/Auth/RegisterController.php` - handle registration
- `app/Http/Controllers/Auth/ForgotPasswordController.php` - handle password reset request
- `app/Http/Controllers/Auth/ResetPasswordController.php` - handle password reset
- `app/Http/Controllers/Auth/VerifyEmailController.php` - handle email verification

### 7.2 Dashboard Controller

- `app/Http/Controllers/DashboardController.php` - index() method, return Inertia::render('Dashboard', data)

### 7.3 Organization Controllers

- `app/Http/Controllers/Organizations/OrganizationController.php` - organization CRUD
- `app/Http/Controllers/Organizations/InformationController.php` - update organization info
- `app/Http/Controllers/Organizations/UsersController.php` - manage organization users, invitations
- `app/Http/Controllers/Organizations/AddressController.php` - update organization address
- `app/Http/Controllers/Organizations/NotificationsController.php` - update notification settings
- `app/Http/Controllers/Organizations/SubscriptionController.php` - manage subscriptions
- `app/Http/Controllers/OrganizationInvitationController.php` - handle invitation acceptance/decline

### 7.4 Design Controllers

- `app/Http/Controllers/Designs/DesignController.php` - CRUD operations for designs

### 7.5 Campaign Controllers

- `app/Http/Controllers/Campaigns/CampaignController.php` - CRUD operations for campaigns

### 7.6 Certificate Controllers

- `app/Http/Controllers/Certificates/CertificateController.php` - CRUD operations for certificates
- `app/Http/Controllers/Certificates/BulkImportController.php` - handle bulk certificate imports

### 7.7 Blog Controllers

- `app/Http/Controllers/Blogs/BlogController.php` - blog post listing and viewing

### 7.8 Onboarding Controllers

- `app/Http/Controllers/Onboarding/OnboardingController.php` - handle onboarding flow steps

### 7.9 Settings Controllers

- `app/Http/Controllers/Settings/ProfileController.php` - update user profile
- `app/Http/Controllers/Settings/PasswordController.php` - update password
- `app/Http/Controllers/Settings/AppearanceController.php` - update appearance settings

### 7.10 Admin Controllers

- `app/Http/Controllers/Admin/DashboardController.php` - admin dashboard
- `app/Http/Controllers/Admin/Users/UserController.php` - admin user management
- `app/Http/Controllers/Admin/Organizations/OrganizationController.php` - admin organization management
- `app/Http/Controllers/Admin/DesignTemplates/DesignTemplateController.php` - admin design template management
- `app/Http/Controllers/Admin/Roles/RoleController.php` - admin role management
- `app/Http/Controllers/Admin/Settings/SettingsController.php` - admin settings

### 7.11 Subscription Controller

- `app/Http/Controllers/SubscriptionController.php` - handle Stripe checkout, billing portal, success/cancel pages

### 7.12 API Controllers (if needed)

- `app/Http/Controllers/Api/AuthController.php` - API authentication
- `app/Http/Controllers/Api/UserController.php` - API user endpoints

## Phase 8: Routes

### 8.1 Web Routes

- File: `routes/web.php`
- Public routes: home, pricing, terms, privacy, cookie policy, contact, templates
- Auth routes: login, register, password reset, email verification (using Inertia)
- Protected routes: dashboard, designs, campaigns, certificates, blogs, onboarding, settings, organizations
- Subscription routes: checkout, success, cancel, billing-portal
- Organization invitation routes: accept, decline

### 8.2 Admin Routes

- File: `routes/admin.php`
- Admin middleware group
- Routes: dashboard, users, organizations, design-templates, roles, settings

### 8.3 API Routes

- File: `routes/api.php`
- Sanctum middleware group
- Routes: auth/login, auth/logout, user/me

### 8.4 Auth Routes

- File: `routes/auth.php`
- Laravel Breeze/Jetstream auth routes (if using) or custom auth routes

## Phase 9: Form Requests & Validation

### 9.1 Create Form Requests

- `app/Http/Requests/StoreOrganizationRequest.php` - organization creation validation
- `app/Http/Requests/UpdateOrganizationRequest.php` - organization update validation
- `app/Http/Requests/InviteUserRequest.php` - user invitation validation
- `app/Http/Requests/StoreDesignRequest.php` - design creation validation
- `app/Http/Requests/UpdateDesignRequest.php` - design update validation
- `app/Http/Requests/StoreCampaignRequest.php` - campaign creation validation
- `app/Http/Requests/UpdateCampaignRequest.php` - campaign update validation
- `app/Http/Requests/StoreCertificateRequest.php` - certificate creation validation
- `app/Http/Requests/BulkImportCertificatesRequest.php` - bulk import validation
- `app/Http/Requests/UpdateProfileRequest.php` - profile update validation
- `app/Http/Requests/UpdatePasswordRequest.php` - password update validation

### 9.2 Validation Rules

- Organization name/email uniqueness within context
- Organization user invitation validation
- File upload validation for media library
- Certificate data validation
- Campaign variable mapping validation

## Phase 10: Services

### 10.1 Create Services

- `app/Services/SubscriptionService.php` - subscription plan management, Stripe integration
- `app/Services/OrganizationService.php` - organization operations, user invitations
- `app/Services/CertificateService.php` - certificate generation, PDF creation
- `app/Services/CampaignService.php` - campaign execution, certificate generation
- `app/Services/MediaService.php` - media upload handling, temporary URL generation

### 10.2 Service Methods

- SubscriptionService: getPlans(), createCheckoutSession(), handleWebhook()
- OrganizationService: create(), inviteUser(), acceptInvitation(), switchOrganization()
- CertificateService: generate(), generateFromCampaign(), createPDF(), revoke()
- CampaignService: execute(), generateCertificates(), complete()
- MediaService: upload(), getTemporaryUrl(), delete()

## Phase 11: Jobs & Events

### 11.1 Create Jobs

- `app/Jobs/ProcessCertificateImport.php` - process bulk certificate imports
- `app/Jobs/UpdateStripeCustomersForOrganization.php` - sync organization data to Stripe
- `app/Jobs/GenerateCertificatePDF.php` - generate PDF certificates asynchronously
- `app/Jobs/SendCampaignCompletionNotifications.php` - send notifications when campaign completes

### 11.2 Create Events

- `app/Events/CampaignArchived.php` - campaign archived event
- `app/Events/CampaignCompleted.php` - campaign completed event
- `app/Events/OrganizationCreated.php` - organization created event
- `app/Events/UserInvitedToOrganization.php` - user invitation event

### 11.3 Create Listeners

- `app/Listeners/LogCampaignCompletion.php` - log campaign completion
- `app/Listeners/SendCampaignCompletionNotifications.php` - send notifications
- `app/Listeners/CreateStripeCustomerForOrganization.php` - create Stripe customer on organization creation

## Phase 12: Observers

### 12.1 Create Observers

- `app/Observers/OrganizationObserver.php` - handle organization events (created, updated, deleted)
- Methods: created() - create Stripe customer, updated() - sync to Stripe, deleted() - cleanup

## Phase 13: Seeders & Factories

### 13.1 Create Seeders

- `database/seeders/DatabaseSeeder.php` - main seeder
- `database/seeders/RolesAndPermissionsSeeder.php` - seed roles and permissions
- `database/seeders/UserSeeder.php` - seed test users
- `database/seeders/OrganizationSeeder.php` - seed test organizations
- `database/seeders/DesignTemplateSeeder.php` - seed design templates

### 13.2 Create Factories

- `database/factories/UserFactory.php` - user factory
- `database/factories/OrganizationFactory.php` - organization factory
- `database/factories/DesignFactory.php` - design factory
- `database/factories/CampaignFactory.php` - campaign factory
- `database/factories/CertificateFactory.php` - certificate factory
- `database/factories/BlogPostFactory.php` - blog post factory

## Phase 14: Configuration Files

### 14.1 Update Config Files

- `config/permission.php` - Spatie Permissions configuration with teams
- `config/media-library.php` - Media Library configuration
- `config/cashier.php` - Cashier/Stripe configuration
- `config/app.php` - application configuration
- `config/auth.php` - authentication configuration
- `config/filesystems.php` - filesystem configuration (S3/local)

### 14.2 Environment Variables

- Database credentials
- Stripe keys (STRIPE_KEY, STRIPE_SECRET, STRIPE_WEBHOOK_SECRET)
- AWS S3 credentials (if using S3 for media)
- Mail configuration
- Queue configuration

## Phase 15: Testing

### 15.1 Feature Tests

- `tests/Feature/Auth/LoginTest.php` - test login flow
- `tests/Feature/Auth/RegisterTest.php` - test registration flow
- `tests/Feature/Organizations/OrganizationTest.php` - test organization CRUD
- `tests/Feature/Organizations/InvitationTest.php` - test user invitations
- `tests/Feature/Designs/DesignTest.php` - test design CRUD
- `tests/Feature/Campaigns/CampaignTest.php` - test campaign CRUD
- `tests/Feature/Certificates/CertificateTest.php` - test certificate CRUD
- `tests/Feature/Subscriptions/SubscriptionTest.php` - test subscription flow

### 15.2 Unit Tests

- `tests/Unit/Models/UserTest.php` - test User model methods
- `tests/Unit/Models/OrganizationTest.php` - test Organization model methods
- `tests/Unit/Services/OrganizationServiceTest.php` - test service methods

## Phase 16: Build & Deployment

### 16.1 Build Configuration

- Configure Vite for production builds
- Setup asset compilation
- Configure environment-specific builds

### 16.2 Deployment Checklist

- Run migrations
- Run seeders (production-safe)
- Configure queue workers
- Configure Horizon (if using)
- Setup S3 bucket (if using)
- Configure Stripe webhooks
- Setup SSL certificates
- Configure domain

## Key Implementation Details

### UUID Usage

- All primary keys use UUIDs via `HasUuids` trait
- Route model binding automatically handles UUIDs
- Foreign keys use UUID type in migrations

### Spatie Permissions Teams

- Permissions are scoped to organizations (teams)
- Use `setPermissionsTeamId($organizationId)` before permission checks
- Share organization context via Inertia middleware
- Check permissions: `$user->can('permission')` within organization context

### Spatie Media Library

- Models implement `HasMedia` interface
- Use `InteractsWithMedia` trait
- Register media collections in `registerMediaCollections()`
- Register conversions in `registerMediaConversions()`
- Use temporary signed URLs for private S3 storage
- Create Vue component for media uploads

### Organization Context

- Store `organization_id` in session
- Set via middleware before requests
- Share via Inertia middleware
- Use `HasOrganizationScope` trait for model scoping
- Organization switching updates session and Inertia shared data

### Inertia Forms

- Use `useForm()` composable from `@inertiajs/vue3`
- Handle validation errors in Vue components
- Use Inertia's `Link` component for navigation
- Use Inertia's `router` for programmatic navigation
- Handle file uploads with proper encoding

### Vue Component Structure

- Use Composition API (setup script)
- Use composables for reusable logic
- Use Inertia's `Head` component for page titles
- Use Inertia's `Link` component for navigation
- Display validation errors from Inertia errors object
- Use flash messages from Inertia shared data