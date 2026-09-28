# Feature Plan: Role-Based Access Control (RBAC) & Permission System

## 1. Overview
This plan outlines the architecture and implementation strategy for adding granular module-based permission management for administrative users in the ERP system. 

The system utilizes a **Role-Based Access Control (RBAC)** model where permissions are assigned to roles, and admin users are assigned a single primary role.

---

## 2. Key Requirements & Business Logic
* **Single Role Assignment:** Each user belongs to exactly one role via a `role_id` on the `users` table.
* **Unified Role System:** The `roles` table includes an `is_admin` flag so customer/user-side roles can share the same structure in the future.
* **Admin Verification:** Access to the admin panel is determined by checking if the assigned user role has `is_admin = true`.
* **Hierarchical Actions ("Write implies Read"):** Explicitly granting `write` permission automatically grants implicit `read` access to that module.
* **Granular UI Control:** Checkboxes for module actions (`read`, `write`, etc.) allow fine-tuning access per role.

---

## 3. Database Schema Design

### A. `roles` Table
Stores system roles for both admin and non-admin scopes.
| Column | Type | Description |
| :--- | :--- | :--- |
| `id` | BigIncrements | Primary Key |
| `name` | String | Unique identifier slug (e.g., `super_admin`, `finance_manager`) |
| `label` | String | Human-readable name (e.g., "Finance Manager") |
| `is_admin` | Boolean | `true` for admin panel access, `false` for standard user roles |
| `timestamps` | Nullable Timestamps | Standard Laravel timestamps |

### B. `permissions` Table
Stores discrete system capabilities by module and action.
| Column | Type | Description |
| :--- | :--- | :--- |
| `id` | BigIncrements | Primary Key |
| `module` | String | Target module slug (e.g., `invoices`, `employees`, `inventory`) |
| `action` | String | Capability type (e.g., `read`, `write`, `delete`) |
| `timestamps` | Nullable Timestamps | Standard Laravel timestamps |

> **Note on Unique Constraint:** A composite unique index should exist on `(module, action)` to avoid duplicate entries.

### C. `permission_role` (Pivot Table)
Maps permissions to roles in a Many-to-Many relationship.
| Column | Type | Description |
| :--- | :--- | :--- |
| `role_id` | Foreign Key | References `id` on `roles` table (Cascade on Delete) |
| `permission_id` | Foreign Key | References `id` on `permissions` table (Cascade on Delete) |

### D. Updates to `users` Table
Adds foreign key linking users to a single role.
| Column Modification | Type | Description |
| :--- | :--- | :--- |
| `role_id` | Foreign Key (Nullable) | References `id` on `roles` table (`onDelete('set null')`) |

---

## 4. Package Choice vs. Custom Build Analysis

### Recommended Approach: Package (`spatie/laravel-permission`)
Using **Spatie Laravel-Permission** is recommended to handle security edge cases, caching performance, and standard Laravel integration.

#### Why Spatie fits this design:
* **Battle-Tested:** Industry-standard package maintained by the community.
* **Built-in Caching:** Automatically caches permissions per request to prevent redundant database queries on every page load or UI component render.
* **Native Blade & Gate Directives:** Works out-of-the-box with standard Laravel features like `@can('write invoices')`, `$user->can(...)`, and route middleware.
* **Extensible:** Supports single-role assignments out of the box using `$user->assignRole('finance_manager')`.

*(Note: If a minimal custom setup is preferred later to reduce external dependencies, standard Eloquent `belongsTo` and `belongsToMany` relationships can be used alongside custom Gates.)*

---

## 5. Architectural Flow & Logic

### A. System Constants & Enums

Define all available modules and actions in a central PHP Enum or Config file to keep seeders, checks, and UI forms synchronized.

* **Modules:** `INVOICES`, `EMPLOYEES`, `INVENTORY`, `REPORTS`, etc.
* **Actions:** `READ`, `WRITE`, `DELETE`.

### B. Admin Gate Check

Update the `isAdmin()` helper method on the `User` model:

```php
public function isAdmin(): bool
{
    return $this->role && $this->role->is_admin;
}
```

### C. Permission Enforcement Logic ("Write Implies Read")

When evaluating read access for a module, the logic checks if the role possesses either read OR write permissions for that module.

* Read Access: Checked via hasPermission('module', 'read') OR hasPermission('module', 'write').

* Write Access: Checked strictly via hasPermission('module', 'write').

* Super Admin Bypass: A Super Admin role automatically passes all permission gates without requiring individual rows in the pivot table.

## 6. Implementation Roadmap (Phased)

### Phase 1: Database Setup

Create and run migrations for roles, permissions, and permission_role.

Add role_id column to the users table.

Build seeders to populate initial admin roles (e.g., Super Admin) and default module permissions.

### Phase 2: Core Models & Relationships

Define Eloquent relationships on User, Role, and Permission models.

Implement the isAdmin() method on the User model.

Configure central constants/enums for module names and permission actions.

### Phase 3: Authorization Engine

Set up Laravel Gates/Policies or Spatie Middleware to intercept incoming HTTP requests.

Implement the "write implies read" checking logic inside custom Gate definitions.

### Phase 4: Role & Permission Management UI

Create an Admin UI screen to view and create Roles.

Build a checklist matrix interface showing Modules vs Actions (Read, Write) to easily assign permissions to a role.

Build a user-assignment interface to select a Role for an admin user from a dropdown.

### Phase 5: UI Element Conditoning & Middleware

Wrap sidebar navigation items and page action buttons in @can / permission checks so users only see options available to their assigned role.

Protect admin route groups using permission middleware per module.
