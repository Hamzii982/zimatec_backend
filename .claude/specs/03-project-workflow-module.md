---
# Spec: Project Workflow Module

## Overview
Introduce a Kanban-style "Project Workflow" module to ZiMaTech so any team
member can follow a project from the initial customer **Anfrage** (inquiry)
through to delivery and closure, moving a project card across the four
workshop stations: **Büro** (Office) → **Konstruktion** (Design) →
**Werkstatt** (Workshop) → **Geschäftsleitung** (Management sign-off). Each
station owns a list of verification steps; the user currently assigned to
the stage can add completion goals to upcoming steps, mark steps as done
sequentially, and hand the project off to the next stage, which triggers an
in-app notification to the next responsible user. The board view is the
centrepiece of the module — animated card movement, role-based filtering,
and inline step progress — and is rendered as a PWA-friendly frontend
that lives behind the same `auth` middleware as the existing printer
problems module.

## Module flag
`projects`. The flag is already `true`, no flip is required. The
`project-workflow` feature is added under the same umbrella so that
admins do not need a second toggle to see the board.

## Depends on
- Existing `Project` model and `projects` table (migration
  `2025_10_27_142603_create_projects_table.php` and later alters).
- Existing `Notification` model and `notifications` table
  (`2025_11_07_112042_create_notifications_table.php` plus
  `2025_11_07_151155_add_url_field_to_natifications_table.php`) for
  delivering stage-handoff notifications.
- Existing `User` model with `role` column
  (`2025_10_31_160746_add_role_to_users_table.php`) for role-based board
  filtering and `@auth('admin')` checks.
- `app/Helpers/NotificationHelper.php::new_notification()` for unified
  in-app + WebPush notification dispatch.
- Existing sidebar/notification rendering in
  `resources/views/admin/partials/sidebar.blade.php` to surface a new
  "Projekt-Workflow" entry.
- Bootstrap 5 + Bootstrap Icons + Tailwind v4 + SweetAlert2 already
  shipped via `resources/sass/app.scss` and `resources/js/app.js`.

## Routes
Grouped the way `routes/web.php` does. All new routes sit under
`Route::middleware(['auth'])` and are additionally protected by a
`workflow.access` middleware that grants access to any authenticated user
(staff or admin) — matching the printer problems posture (printer
problems themselves are inside `Route::middleware(['auth'])` and are
read/write for any logged-in user; we do the same for the workflow board).

Auth (`auth`):
- `GET  /workflow` — show the Kanban board with projects grouped by stage
  — `auth`
- `GET  /workflow/projects/{project}` — show project workflow detail
  (steps, history, completion goals) — `auth`
- `POST /workflow/projects/{project}/steps/{step}/goals` — add a
  completion goal to a step (current assignee only) — `auth`
- `DELETE /workflow/projects/{project}/steps/{step}/goals/{goal}` — remove
  a completion goal (author or admin) — `auth`
- `POST /workflow/projects/{project}/steps/{step}/complete` — mark a step
  as done sequentially (only the current step in the current stage) —
  `auth`
- `POST /workflow/projects/{project}/advance` — advance project to the
  next stage (only when all current-stage steps are completed) — `auth`
- `POST /workflow/projects/{project}/reassign` — reassign current stage
  owner to another user — `auth` (admin or current assignee)
- `GET  /workflow/projects/{project}/history` — JSON activity feed
  (stage transitions, step completions, goal additions) — `auth`

Admin (`auth` + `role:admin`):
- `GET  /admin/workflow/settings` — manage workflow stages and per-stage
  default step templates — `admin`
- `POST /admin/workflow/stages` — create a new workflow stage — `admin`
- `PUT  /admin/workflow/stages/{stage}` — update stage (name, color,
  order, role requirement) — `admin`
- `DELETE /admin/workflow/stages/{stage}` — deactivate a stage — `admin`
- `POST /admin/workflow/stages/{stage}/steps` — add a default step
  template to a stage — `admin`
- `PUT  /admin/workflow/steps/{step}` — update step template (name,
  description, order, required) — `admin`
- `DELETE /admin/workflow/steps/{step}` — remove step template —
  `admin`
- `POST /admin/workflow/projects/{project}/assign` — manually attach a
  project to the workflow and pin its starting stage — `admin`

The detail view (`GET /workflow/projects/{project}`) returns a
self-contained HTML page that uses an AlpineJS / vanilla JS controller to
animate card transitions and inline goals; no separate SPA.

## Database changes
All migrations are additive. Tables do not yet exist; verified against
the 71 migrations under `database/migrations/`.

- `2026_07_22_100000_create_workflow_stages_table.php`
  - `id` (bigint, PK)
  - `key` (string, unique) — machine identifier, e.g. `office`,
    `design`, `workshop`, `management`
  - `name` (string) — display name (German-first)
  - `color` (string, default `#0d6efd`) — card / column accent
  - `icon` (string, nullable) — Bootstrap Icons class
  - `order_index` (unsigned int) — controls column left-to-right order
  - `required_role` (string, nullable) — `admin`, `user`, or null
  - `is_active` (boolean, default true)
  - `timestamps`
- `2026_07_22_100001_create_workflow_steps_table.php`
  - `id` (bigint, PK)
  - `stage_id` (FK → `workflow_stages.id`, cascade on delete)
  - `name` (string) — step title (German-first)
  - `description` (text, nullable)
  - `order_index` (unsigned int) — order inside the stage
  - `is_required` (boolean, default true) — must be completed to advance
  - `timestamps`
- `2026_07_22_100002_create_workflow_projects_table.php`
  - `id` (bigint, PK)
  - `project_id` (FK → `projects.id`, cascade on delete, unique — one
    workflow per project)
  - `current_stage_id` (FK → `workflow_stages.id`, restrict on delete)
  - `current_assignee_id` (FK → `users.id`, nullable, set null on delete)
  - `started_at` (timestamp, nullable)
  - `completed_at` (timestamp, nullable)
  - `timestamps`
- `2026_07_22_100003_create_workflow_project_steps_table.php`
  - `id` (bigint, PK)
  - `workflow_project_id` (FK → `workflow_projects.id`, cascade)
  - `step_id` (FK → `workflow_steps.id`, cascade)
  - `status` (enum `pending|in_progress|completed`, default `pending`)
  - `started_at` (timestamp, nullable)
  - `completed_at` (timestamp, nullable)
  - `completed_by` (FK → `users.id`, nullable, set null on delete)
  - `note` (text, nullable) — completion comment
  - `order_index` (unsigned int) — copy from `workflow_steps` at attach
    time so we can reorder without breaking history
  - `timestamps`
  - Unique `(workflow_project_id, step_id)`.
- `2026_07_22_100004_create_workflow_step_goals_table.php`
  - `id` (bigint, PK)
  - `workflow_project_step_id` (FK → `workflow_project_steps.id`,
    cascade)
  - `body` (text) — goal description
  - `created_by` (FK → `users.id`, set null on delete)
  - `is_completed` (boolean, default false)
  - `completed_at` (timestamp, nullable)
  - `timestamps`
- `2026_07_22_100005_create_workflow_activities_table.php`
  - `id` (bigint, PK)
  - `workflow_project_id` (FK → `workflow_projects.id`, cascade)
  - `actor_id` (FK → `users.id`, nullable, set null on delete)
  - `type` (string) — `stage_advanced`, `step_completed`, `goal_added`,
    `goal_completed`, `assignee_changed`
  - `payload` (json, nullable) — `{ from_stage_id, to_stage_id, step_id,
    goal_id, … }`
  - `created_at` (timestamp) — no `updated_at`
- `2026_07_22_100006_add_user_department_to_users_table.php` (optional
  convenience column for default assignee)
  - `department` (string, nullable) — `office`, `design`, `workshop`,
    `management`

Seed data is bundled in the first migration via a dedicated seeder
(`database/seeders/WorkflowStageSeeder.php`) so the four canonical stages
land in every environment. The seeder is wired into `DatabaseSeeder`.

## Eloquent models
- **Create:**
  - `App\Models\Workflow\Stage` — `hasMany(Step::class)`,
    `hasMany(Project::class via 'workflowProjects')`. Fillable
    `key, name, color, icon, order_index, required_role, is_active`.
  - `App\Models\Workflow\Step` — `belongsTo(Stage::class)`,
    `hasMany(ProjectStep::class)`. Fillable
    `stage_id, name, description, order_index, is_required`.
  - `App\Models\Workflow\Project` — `belongsTo(\App\Models\Project::class)`,
    `belongsTo(Stage::class, 'current_stage_id')`,
    `belongsTo(User::class, 'current_assignee_id')`,
    `hasMany(ProjectStep::class)`,
    `hasMany(Activity::class)`. Fillable
    `project_id, current_stage_id, current_assignee_id, started_at,
    completed_at`.
  - `App\Models\Workflow\ProjectStep` —
    `belongsTo(Project::class, 'workflow_project_id')`,
    `belongsTo(Step::class)`, `belongsTo(User::class, 'completed_by')`,
    `hasMany(StepGoal::class)`. Fillable
    `workflow_project_id, step_id, status, started_at, completed_at,
    completed_by, note, order_index`.
  - `App\Models\Workflow\StepGoal` —
    `belongsTo(ProjectStep::class, 'workflow_project_step_id')`,
    `belongsTo(User::class, 'created_by')`. Fillable
    `workflow_project_step_id, body, created_by, is_completed,
    completed_at`.
  - `App\Models\Workflow\Activity` —
    `belongsTo(Project::class, 'workflow_project_id')`,
    `belongsTo(User::class, 'actor_id')`. Fillable
    `workflow_project_id, actor_id, type, payload`.
- **Modify:**
  - `App\Models\Project` — add `workflowProject()` (`hasOne` to
    `App\Models\Workflow\Project`) and `workflowActivities()` (`hasMany`
    through it). No fillable changes.
  - `App\Models\User` — add `assignedWorkflowProjects()` (`hasMany` to
    `App\Models\Workflow\Project` via `current_assignee_id`) and
    `workflowActivities()` (`hasMany` to `App\Models\Workflow\Activity`
    via `actor_id`).

## Controllers
- **Create:**
  - `App\Http\Controllers\Workflow\WorkflowController` — `index(board)`,
    `show(project)`, `history(project)`. Uses user-facing layout
    `user.layouts.index`.
  - `App\Http\Controllers\Workflow\ProjectStepController` —
    `addGoal(project, step)`, `destroyGoal(project, step, goal)`,
    `complete(project, step)`, `reassign(project)`. All write actions
    live in this single controller to keep the board's AJAX surface
    small. `advance(project)` lives here too.
  - `App\Http\Controllers\Admin\Workflow\StageController` — admin
    settings for stages and steps (`index`, `storeStage`,
    `updateStage`, `destroyStage`, `storeStep`, `updateStep`,
    `destroyStep`). Uses admin layout `admin.layouts.index`.
  - `App\Http\Controllers\Admin\Workflow\AssignmentController` — admin
    `attach(project)`, `assign(project)`. Used to wire a project into
    the workflow manually.
- **Modify:**
  - `App\Http\Controllers\Admin\HomeController` — no functional change
    required; the dashboard "recent projects" widget is left as is.
  - `routes/web.php` — register the new groups (auth + admin) and
    import the new controllers.
  - `resources/views/admin/partials/sidebar.blade.php` — add a
    "Projekt-Workflow" entry under the existing
    `config('modules.projects')` block so admins can jump to the board
    from the admin shell.
  - `resources/views/user/partials/sidebar.blade.php` (if present) —
    same idea, for the user shell.

## Middleware / Policies
- New middleware `App\Http\Middleware\EnsureWorkflowAccess` (alias
  `workflow.access`) — short-circuits to 403 when the user is not
  authenticated; placeholder for future role-fine-grained gating
  (e.g. "only `werkstatt` users can move cards in the Werkstatt
  column"). Registered in `bootstrap/app.php` (Laravel 12 style).
- New policy `App\Policies\Workflow\ProjectPolicy` with abilities
  `view`, `update`, `completeStep`, `advance`, `reassign`. The
  `completeStep` and `advance` abilities require the user to be either
  the project's `current_assignee_id`, a member of the required role for
  the stage, or an admin. The policy is auto-resolved by Laravel via the
  convention `App\Models\Workflow\Project` → `App\Policies\Workflow\ProjectPolicy`.
- All admin routes are wrapped in `role:admin` per the existing
  convention; no change to the `role` middleware alias.

## Views
- **Create:**
  - `resources/views/workflow/index.blade.php` — Kanban board. Four
    columns (Büro, Konstruktion, Werkstatt, Geschäftsleitung). Each
    column is a scrollable list of `workflow.projects.partials.card`
    partials. Header row carries the legend, assignee filter, and
    "Meine Projekte" toggle. Uses CSS Grid via Bootstrap 5
    `d-grid`/`row-cols-*` plus a small custom stylesheet
    `resources/css/workflow.css` (registered in `app.scss`).
  - `resources/views/workflow/show.blade.php` — project detail page.
    Shows project meta (name, customer, auftragsnummer), current stage
    pill, the ordered step list with completion checkboxes, the goals
    panel per step, and a history timeline using Bootstrap's
    `list-group` + custom animation classes.
  - `resources/views/user/projects/partials/workflow-card.blade.php` —
    Kanban card partial (extracted so it can be re-rendered via
    fetch() after advance/complete).
  - `resources/views/admin/workflow/settings.blade.php` — admin stage
    and step template manager. Drag-handle list using only CSS
    `cursor: grab` plus an `<input type="hidden" name="order_index">`
    pattern (no jQuery UI / no Sortable.js dependency added).
  - `resources/views/admin/workflow/partials/stage-row.blade.php`,
    `resources/views/admin/workflow/partials/step-row.blade.php` —
    row partials for the settings tables.
  - `resources/views/components/workflow/progress-bar.blade.php` —
    pure Bootstrap progress bar component used in the detail view and
    the card to visualise step completion.
  - `resources/views/components/workflow/stage-pill.blade.php` —
    small rounded pill used everywhere a stage is shown.
- **Modify:**
  - `resources/views/admin/partials/sidebar.blade.php` — add the
    "Projekt-Workflow" link inside the existing `Projektmanagement`
    collapse block.
  - `resources/views/user/partials/sidebar.blade.php` (if it exists) —
    same idea.
  - `resources/sass/app.scss` — `@import
    "../../../node_modules/bootstrap/scss/functions";` already in place;
    add `@import
    "../../css/workflow.css";` or `@use "workflow";` to ship the
    animation rules.

Each new template extends the right layout (`user.layouts.index` for the
board and detail, `admin.layouts.index` for settings) and German-first
UI text. Translation keys live in
`lang/de/workflow.php` and `lang/en/workflow.php` (new files). We also
ship `lang/de.json` / `lang/en.json` updates for any short keys that
should remain short-string-keyed.

## Files to change
- `routes/web.php` — register the new route groups and import the
  controllers.
- `bootstrap/app.php` — register the new `workflow.access` middleware
  alias (Laravel 12 style).
- `app/Models/Project.php` — add `workflowProject()` and
  `workflowActivities()` relationships.
- `app/Models/User.php` — add `assignedWorkflowProjects()` and
  `workflowActivities()` relationships.
- `resources/views/admin/partials/sidebar.blade.php` — surface the
  new "Projekt-Workflow" link.
- `resources/views/user/partials/sidebar.blade.php` — same (if file
  exists).
- `resources/sass/app.scss` — register the new `workflow.css`
  stylesheet.
- `database/seeders/DatabaseSeeder.php` — call
  `WorkflowStageSeeder` after the existing seeders.
- `lang/de.json` and `lang/en.json` — add the short keys used by
  SweetAlert prompts.
- `tests/Feature/ProjectWorkflowTest.php` — new Pest feature test
  (described in the Definition of Done).

## Files to create
- `database/migrations/2026_07_22_100000_create_workflow_stages_table.php`
- `database/migrations/2026_07_22_100001_create_workflow_steps_table.php`
- `database/migrations/2026_07_22_100002_create_workflow_projects_table.php`
- `database/migrations/2026_07_22_100003_create_workflow_project_steps_table.php`
- `database/migrations/2026_07_22_100004_create_workflow_step_goals_table.php`
- `database/migrations/2026_07_22_100005_create_workflow_activities_table.php`
- `database/migrations/2026_07_22_100006_add_user_department_to_users_table.php`
- `database/seeders/WorkflowStageSeeder.php`
- `app/Models/Workflow/Stage.php`
- `app/Models/Workflow/Step.php`
- `app/Models/Workflow/Project.php`
- `app/Models/Workflow/ProjectStep.php`
- `app/Models/Workflow/StepGoal.php`
- `app/Models/Workflow/Activity.php`
- `app/Http/Controllers/Workflow/WorkflowController.php`
- `app/Http/Controllers/Workflow/ProjectStepController.php`
- `app/Http/Controllers/Admin/Workflow/StageController.php`
- `app/Http/Controllers/Admin/Workflow/AssignmentController.php`
- `app/Http/Middleware/EnsureWorkflowAccess.php`
- `app/Policies/Workflow/ProjectPolicy.php`
- `app/Http/Requests/Workflow/StoreGoalRequest.php`
- `app/Http/Requests/Workflow/CompleteStepRequest.php`
- `app/Http/Requests/Workflow/AdvanceProjectRequest.php`
- `app/Http/Requests/Workflow/ReassignProjectRequest.php`
- `app/Services/Workflow/WorkflowService.php`
- `app/Services/Workflow/StageAdvancer.php`
- `resources/views/workflow/index.blade.php`
- `resources/views/workflow/show.blade.php`
- `resources/views/user/projects/partials/workflow-card.blade.php`
- `resources/views/admin/workflow/settings.blade.php`
- `resources/views/admin/workflow/partials/stage-row.blade.php`
- `resources/views/admin/workflow/partials/step-row.blade.php`
- `resources/views/components/workflow/progress-bar.blade.php`
- `resources/views/components/workflow/stage-pill.blade.php`
- `resources/css/workflow.css`
- `public/js/workflow.js` (referenced from the board view; uses
  fetch + Web Animations API, no new vendor)
- `lang/de/workflow.php`
- `lang/en/workflow.php`
- `tests/Feature/ProjectWorkflowTest.php`

## New composer packages
No new dependencies. The animations use the native Web Animations API
plus existing Bootstrap 5 utilities; reorder in admin settings uses
plain HTML5 drag-and-drop or simple up/down buttons if drag fails.
Animated toasts reuse SweetAlert2 which is already loaded.

## Rules for implementation
- Follow MVC: routes → Form Requests → controllers (thin) → Eloquent
  models / service classes → Blade views. Business logic that touches
  multiple tables (advancing stages, logging activities, dispatching
  notifications) lives in `App\Services\Workflow\WorkflowService` /
  `StageAdvancer`, not in the controller.
- Use route model binding everywhere (`{project}` resolves to
  `App\Models\Project`; `{workflowProject}` to
  `App\Models\Workflow\Project`).
- All admin routes wrapped in `auth` + `role:admin`. All user routes
  wrapped in `auth` + the new `workflow.access` middleware alias.
- Check `config('modules.projects')` before wiring routes (defensive —
  same pattern as the existing `if (config('modules.tablar'))` blocks).
- Use Laravel Pint (`./vendor/bin/pint`) before committing.
- Parameterised Eloquent queries only — no raw SQL strings with user
  input.
- German UI strings first; add English translations. Reuse
  `lang/de/workflow.php` / `lang/en/workflow.php` for new keys.
- Use Bootstrap 5 + Tailwind v4 utilities already in the project; do
  not introduce a new CSS framework. Animation classes are added to
  `public/css/workflow.css` and registered through `resources/sass/app.scss`.
- No new service providers unless absolutely required; the
  `workflow.access` middleware alias is registered in
  `bootstrap/app.php`.
- All new controllers stay in
  `App\Http\Controllers\Workflow` (user) and
  `App\Http\Controllers\Admin\Workflow` (admin), mirroring the
  existing layout in `App\Http\Controllers\Admin\Settings\…`.
- Notifications on stage handoff go through
  `new_notification('workflow_stage', $message, $url, $userId)` so the
  existing in-app + WebPush pipeline keeps working.
- The board should reload gracefully after a fetch — use AlpineJS
  `x-data` only for the open/close goal dialog; the actual card
  re-render is done via `fetch().then(html => swapElement(...))` to keep
  the stack vanilla.

## Definition of done
- [ ] All seven new migrations run cleanly on a fresh database and on
      the existing test database (`zimatech_test`). `php artisan
      migrate:fresh` succeeds.
- [ ] `WorkflowStageSeeder` is wired into `DatabaseSeeder` and creates
      the four canonical stages with their default step templates.
- [ ] A logged-in non-admin user can visit `/workflow` and see the
      Kanban board with at least one project card per column.
- [ ] A logged-in user who is the `current_assignee_id` of a project
      can add a goal, mark a step as done, and advance the project to
      the next stage; the project card animates from the source column
      to the target column.
- [ ] When a project is advanced, a row is written to
      `workflow_activities` (type `stage_advanced`), the next assignee
      receives a `Notification` (`type = 'workflow_stage'`), and the
      URL points to the new project detail page.
- [ ] A non-assignee non-admin user cannot complete steps or advance
      projects (returns 403 via the policy).
- [ ] Admin can create / edit / reorder / deactivate stages and step
      templates at `/admin/workflow/settings` without breaking existing
      project data.
- [ ] Pest test `tests/Feature/ProjectWorkflowTest.php` covers:
      1. board lists projects by current stage,
      2. completing a step moves it to `completed` and writes an
         activity row,
      3. advancing a stage dispatches a notification to the new
         assignee,
      4. unauthorised users get 403 on advance.
- [ ] All new files are formatted with `./vendor/bin/pint` and the
      test suite (`composer test`) passes locally.
- [ ] Translations: every German string used in the new views has a
      matching key in `lang/de/workflow.php` and a mirror in
      `lang/en/workflow.php`.
