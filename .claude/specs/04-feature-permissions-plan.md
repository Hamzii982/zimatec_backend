# Feature Plan: Role-Based Access Control (RBAC) and Module Permissions

## 1. Objective

This plan adds a real role and permission system to the current ZiMaTec Laravel app so different admin users can be granted different access to modules and actions. The implementation must fit the project's current architecture, which is still built around:

- a simple `users.role` string field
- custom admin middleware `role:admin`
- module toggles from `config/modules.php`
- route groups protected by `Route::middleware(['auth', 'role:admin'])`
- an admin sidebar that renders menu items based on `config('modules.*')`

This is not a greenfield permission system. It must be introduced in a way that preserves the current behavior while allowing future granular authorization.

---

## 2. Current project structure that matters for RBAC

### 2.1 Authentication and role model today

Current user role logic is very simple and lives in:

- `app/Models/User.php`
- `app/Http/Middleware/RoleMiddleware.php`

Relevant behavior:

- `User` has a `role` string field, not a `role_id` foreign key yet.
- `User::isAdmin()` checks `return $this->role === 'admin';`.
- `RoleMiddleware` checks `auth()->user()->role !== $role` and aborts with 403.

This means the app currently uses a string-based role check, and the admin gate is effectively a fixed admin/user split.

### 2.2 Admin routes and access control

Admin routing is centralized in:

- `routes/web.php`

The admin group uses:

```php
Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () { ... });
```

This is the main place permissions will need to be integrated. Every admin module is currently treated as admin-only, but there is no per-module or per-role access layer beneath that.

### 2.3 Module flags currently define visibility

The app has feature flags in:

- `config/modules.php`

Current modules include:

- `teams`
- `projects`
- `time`
- `suppliers`
- `settings`
- `feedback`
- `tablar`
- `scheduler`
- `project_offers`
- `emails`

These flags are used to decide whether a whole section appears in routes and sidebar menu. This is a useful starting point for role-per-module visibility, but the current flag system is not permission-aware.

### 2.4 Admin UI structure

The main admin layout and sidebar are in:

- `resources/views/admin/layouts/index.blade.php`
- `resources/views/admin/partials/sidebar.blade.php`

The sidebar already separates sections like:

- Team
- Projectmanagement
- Zeit Management
- Lieferant Management
- Lager Management
- Einstellungen
- Email Management
- Feedback Management

This is the exact place where role-based visibility must be applied later.

### 2.5 Existing domain structure that can be used as permission modules

The project is already organized by business domains. These are the practical admin modules to expose in permissions:

- `teams` / Users
- `projects` / Bauteile / Project management
- `time` / Time logs / time approval
- `suppliers`
- `feedback`
- `tablar` / Lager / Shelf management
- `settings`
- `scheduler`
- `emails`
- `project_offers`

This structure is more realistic than inventing a new permission taxonomy.

---

## 3. What is already present and what is missing

### Already present

- `users.role` string field
- `RoleMiddleware` for admin access
- module toggles in `config/modules.php`
- admin sections grouped by business area
- `UserController` for admin user management
- settings pages under `app/Http/Controllers/Admin/Settings/*`
- permissions-like logic is not yet implemented as a real authorization layer

### Missing

- `roles` table
- `permissions` table
- `role_user`/`permission_role` pivot tables
- `role_id` on `users` (or a migration path from existing `role` string)
- CRUD for roles in admin settings
- permission matrix UI in a role editor
- authorization checks for sidebar, routes, and controller actions
- user assignment to roles in the admin users management screen

---

## 4. Recommended architecture for this project

### 4.1 Do not replace the current `role` concept abruptly

Because the current app depends on `users.role` and `role:admin` middleware, the RBAC rollout should be phased.

Recommended compatibility approach:

1. Add new `roles` and `permissions` tables.
2. Add `users.role_id` as the new source of truth.
3. Keep the existing `users.role` string temporarily for compatibility, but treat it as legacy.
4. Add a helper such as `User::isAdmin()` that resolves via `role_id` first, then falls back to the legacy string.
5. Replace hard-coded `role:admin` checks gradually with permission-based checks.

This avoids breaking existing routes and user management while moving to a proper RBAC model.

### 4.2 Use module-level permissions rather than deep object-level permissions

This app is organized as module sections, so the permission system should be module-based rather than trying to invent thousands of resource permissions too early.

Suggested permission action set:

- `view`
- `create`
- `update`
- `delete`
- `manage`

For simplicity in this app, a strong initial version can be:

- `read`
- `write`

The logic can remain simple and still reflect the project needs.

### 4.3 "Write implies read" should be implemented at the gate level

This matches the plan and is a good fit for the project:

- If a role has `write` on a module, it should also be allowed to read it.
- If a role has `read` on a module, it should only be able to view that module.
- `delete` can remain a separate action or be mapped to `write` for simpler first implementation.

This keeps the RBAC model aligned with the current admin operations and avoids over-engineering before it is needed.

---

## 5. Exact implementation steps for this project

### Phase 1: Database and models

#### Step 1.1 — Create core RBAC tables

Add new migrations in `database/migrations/`:

- `create_roles_table.php`
- `create_permissions_table.php`
- `create_permission_role_table.php`
- `add_role_id_to_users_table.php`

Recommended schema:

- `roles`
  - `id`
  - `name` (unique slug, e.g. `super_admin`, `team_manager`)
  - `label` (human readable)
  - `is_admin` (boolean)
  - timestamps

- `permissions`
  - `id`
  - `module` (e.g. `projects`, `time`, `settings`, `tablar`)
  - `action` (e.g. `read`, `write`, `delete`)
  - timestamps
  - unique index on `(module, action)`

- `permission_role`
  - `role_id`
  - `permission_id`
  - unique index on `(role_id, permission_id)`

- `users.role_id`
  - nullable foreign key to `roles.id`

#### Step 1.2 — Create models

Create or update these models:

- `app/Models/Role.php`
- `app/Models/Permission.php`
- `app/Models/User.php` (add role relationship and permission helper methods)

Suggested relationships:

```php
class User extends Authenticatable
{
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'permission_role', 'role_id', 'permission_id')
            ->through(Role::class);
    }
}
```

For this project, the safest pattern is to attach permissions via the role and then resolve them through `$user->role->permissions`.

#### Step 1.3 — Backfill legacy roles

Current users already have a `role` string such as `admin` or `user`.

During migration, create a corresponding `Role` record and assign `role_id` for existing users. This must happen via a migration or a one-time seeder.

Important: this is a compatibility step; do not destroy the legacy string immediately.

---

### Phase 2: Authorization helpers and middleware

#### Step 2.1 — Add a permission helper on `User`

Add methods like:

- `hasRole(string $roleName): bool`
- `isAdmin(): bool` (resolve via `role_id` + fallback to legacy `role`)
- `canAccessModule(string $module, string $action = 'read'): bool`
- `canView(string $module): bool`
- `canManage(string $module): bool`

This is the central logic point for all future UI and route checks.

#### Step 2.2 — Replace or wrap `RoleMiddleware`

Current middleware:

- `app/Http/Middleware/RoleMiddleware.php`

This should evolve to support both:

- legacy `role:admin` checks
- future `permission:module,action` checks or `can:module` checks

Recommended compatibility guard:

```php
if (! auth()->check()) abort(403);
if (auth()->user()->isAdmin()) return $next($request);
if (auth()->user()->canAccessModule('settings', 'read')) return $next($request);
abort(403);
```

This prevents immediate breakage while the old admin role system is still in place.

---

### Phase 3: Role and permission management in admin settings

This is the most important project-specific step: adding a proper admin CRUD area for roles inside the current settings module.

#### Step 3.1 — Add settings routes for roles

Extend routes in:

- `routes/web.php`

Add a new settings group under the admin section, for example:

- `admin/settings/roles`
- `admin/settings/roles/create`
- `admin/settings/roles/{role}/edit`

These routes belong under the current settings area because the app already groups settings in:

- `app/Http/Controllers/Admin/Settings/`
- `resources/views/admin/settings/`

#### Step 3.2 — Create controller

New controller to add:

- `app/Http/Controllers/Admin/Settings/RoleController.php`

Responsibilities:

- index all roles
- create a role
- store a role
- edit a role
- update a role
- delete a role
- show the permission grid for the role

#### Step 3.3 — Add views

Create a new section under:

- `resources/views/admin/settings/roles/`

Views to include:

- `index.blade.php` — list all roles
- `create.blade.php` — form for name, label, is_admin flag
- `edit.blade.php` — same form + permission matrix
- partial `_permissions_matrix.blade.php` — module/action checkboxes

This should match the app’s current admin visual style and keep the same German labels used elsewhere.

#### Step 3.4 — Permission matrix UI

The permission matrix should use the modules already present in the project.

Suggested module list:

- `teams`
- `projects`
- `time`
- `suppliers`
- `settings`
- `feedback`
- `tablar`
- `scheduler`
- `emails`
- `project_offers`

Suggested actions:

- `read`
- `write`
- `delete`

The UI should look like a checklist matrix. Example:

- Teams → read / write
- Projects → read / write / delete
- Lager → read / write / delete
- Settings → read / write

This is a natural fit for the current `config/modules.php` and admin sidebar structure.

---

### Phase 4: User role assignment in the existing user management

The project already has admin user management in:

- `app/Http/Controllers/Admin/UserController.php`
- `resources/views/admin/users/`

This must be updated so that each user can be assigned a role from the roles table.

#### Required updates

- add a `role_id` dropdown in the create/edit user form
- keep the legacy `role` field working during migration
- show the active role label in the user table/profile page
- validate role existence before storing

This is important because the project currently has user screens like:

- `resources/views/admin/users/edit.blade.php`
- `resources/views/admin/users/profile.blade.php`

and there is also profile logic in `app/Http/Controllers/Admin/UserController.php`.

---

### Phase 5: Conditional routing and conditional UI rendering

This part should be added in the same style as the app’s existing `config/modules.php` toggles.

#### Step 5.1 — Route-level conditional access

Add permission checks to route groups like:

- `admin` route groups
- `projects` route groups
- `time` route groups
- `tablar` route groups
- `settings` route groups

Examples:

```php
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/settings', ...)->middleware('can:view,settings');
});
```

Since the current project uses simple string-role checks, the migration can happen in stages:

- first: allow only admins to access current admin routes
- next: allow per-module access checks based on role permissions
- finally: remove hard-coded admin-only routes from the main role check where not needed

#### Step 5.2 — Sidebar conditional rendering

The sidebar is already the main permission gate for the UI:

- `resources/views/admin/partials/sidebar.blade.php`

This should become conditional based on the current user’s role permissions, for example:

```blade
@can('view', 'projects')
    ... menu item ...
@endcan
```

or a custom helper:

```blade
@if(auth()->user()->canAccessModule('projects', 'read'))
    ... show project menu ...
@endif
```

This is the easiest way to hide sections like:

- Settings
- Time Management
- Lager Management
- Supplier Management
- Workflow management

based on the assigned role.

#### Step 5.3 — Action button conditionals

In addition to top-level menu sections, any row actions, import buttons, form submit buttons, or delete actions should be wrapped with checks. For example:

- create material button
- create user button
- delete supplier button
- workflow settings button
- admin settings pages

This matches how the app already uses `config('modules.*')` but makes it role-aware rather than feature-flag-aware.

---

### Phase 6: Apply permissions to the actual domains in this project

These are the exact business modules to seed and protect first.

#### Team / Users

Files:

- `app/Http/Controllers/Admin/UserController.php`
- `resources/views/admin/users/*`
- `routes/web.php` user routes

Permissions:

- `teams.read`
- `teams.write`

#### Projects / Bauteile / Services

Files:

- `app/Http/Controllers/Admin/ProjectController.php`
- `app/Http/Controllers/Admin/BauteilController.php`
- `app/Http/Controllers/Admin/PositionController.php`
- `routes/web.php` project admin routes

Permissions:

- `projects.read`
- `projects.write`
- `projects.delete`

#### Time Management

Files:

- `app/Http/Controllers/Admin/TimeController.php`
- `routes/web.php` time admin routes

Permissions:

- `time.read`
- `time.write`

#### Suppliers

Files:

- `app/Http/Controllers/Admin/SupplierController.php`
- `routes/web.php` supplier routes

Permissions:

- `suppliers.read`
- `suppliers.write`

#### Lager / Tablar / Shelf

Files:

- `app/Http/Controllers/Admin/AdminLagerController.php`
- `app/Http/Controllers/Admin/TablarController.php`
- `app/Http/Controllers/Admin/ShelfController.php`
- `routes/web.php` lager + shelf + tablar routes

Permissions:

- `tablar.read`
- `tablar.write`
- `tablar.delete`

#### Settings

Files:

- `app/Http/Controllers/Admin/Settings/*`
- `resources/views/admin/settings/*`
- `routes/web.php` settings routes

Permissions:

- `settings.read`
- `settings.write`

#### Feedback

Files:

- `app/Http/Controllers/Admin/FeedbackController.php`
- `routes/web.php` feedback routes

Permissions:

- `feedback.read`
- `feedback.write`

#### Emails

Files:

- `app/Http/Controllers/Admin/EmailController.php`
- `routes/web.php` email routes

Permissions:

- `emails.read`
- `emails.write`

#### Project offers and workflow

Files:

- `app/Http/Controllers/Admin/ProjectOfferController.php`
- `app/Http/Controllers/Admin/Workflow/*`
- `routes/web.php` workflow admin routes

Permissions:

- `project_offers.read`
- `project_offers.write`
- `workflow.read`
- `workflow.write`

---

## 6. Suggested default roles for this project

The app currently only has admin/user roles, but once RBAC is added, these should be seeded as defaults:

1. `super_admin`
   - `is_admin = true`
   - access to all modules
   - created for the existing highest-power admin user

2. `manager`
   - `is_admin = true`
   - access to operational modules such as projects, time, tablar, suppliers

3. `staff`
   - `is_admin = false` or limited admin access
   - access to only UI sections needed for operational work

4. `viewer`
   - read-only access to selected modules

This is useful because the current project already has a shop-floor/ERP split and the app will benefit from read-only operational roles.

---

## 7. Recommended rollout order

1. Add tables and role model.
2. Add legacy compatibility in `User` and `RoleMiddleware`.
3. Add admin role CRUD under settings.
4. Add permission matrix for each role.
5. Add role assignment to user create/edit views.
6. Gate sidebar items and admin routes by module permissions.
7. Add controller-level checks for critical admin actions like delete/update.
8. Remove hard-coded admin-only assumptions only after the new role system is stable.

---

## 8. Acceptance criteria

The feature is done when all of the following are true:

- An admin can create/update/delete roles from the settings area.
- Each role can be assigned permissions for each project module.
- A user can be assigned one role from the admin user page.
- The sidebar hides modules the current user cannot view.
- Admin routes are protected by permission checks instead of only `role:admin`.
- Existing users still continue to work during the migration step.
- The current app behavior remains intact for the default admin role while the new RBAC is introduced.

---

## 9. Scope note

The general RBAC plan is good, but it should not be implemented in a fully generic way. For this project, the permission system should follow the current app’s real structure:

- `config/modules.php` for modules
- `routes/web.php` for admin route groups
- `resources/views/admin/partials/sidebar.blade.php` for visibility
- `app/Http/Controllers/Admin/*` for business actions
- `app/Models/User.php` for current role checks

The permission system should be added as a project-specific admin authorization layer, not as a detached abstraction. This is the safest way to keep the implementation aligned with the actual application architecture.
