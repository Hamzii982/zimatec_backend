# Spec: ERP Auftrag Terminology and Number Links

## Overview
Replace user-facing wording for the ERP's core customer-work entity from “Projekt” to “Auftrag” consistently across the German-first interface and connected modules, and make the ZF/ZT Auftragsnummer in the admin Auftrag list link to the existing Auftrag detail page. This is a terminology and navigation change only: preserve the existing Project model, database schema, field names, route names/URLs, permissions, stored project names, and file-storage paths so related modules and integrations continue to work.

## Module flag
`projects` (already enabled in `config/modules.php`). Keep the flag unchanged. Related screens may be owned by `time`, `scheduler`, `suppliers`, `project_offers`, or other existing modules; do not change their flags.

## Depends on
- Existing `Project` records, company order-number fields (`auftragsnummer_zf` and `auftragsnummer_zt`), and related positions, Bauteile, time records, workflow, supplier processes, and offers.
- Existing admin route `admin.projects.show` and its `role:admin` and `projects` read-access gates.
- Existing German-first copy in Blade templates and `lang/de/workflow.php`; existing English translations in `lang/en/workflow.php` and `lang/en.json`.

## Routes
No new routes.

Existing detail route to reuse:
- `GET /admin/projects/show/{project}` (`admin.projects.show`) — show the existing Auftrag detail page — `admin` with the existing projects read-access gate for the projects module.
- Add Auftragsnummer links only on admin-side pages; do not add them on public/front-user pages.

The route path and route name remain unchanged for compatibility.

## Database changes
No database changes. Do not rename tables, columns, foreign keys, stored values, or existing filesystem paths.

## Eloquent models
- No model changes. Keep `App\Models\Project`, `ProjectStatus`, `Position`, `ProjectOffer`, `SupplierProject`, and existing relationships and field names unchanged.
- Continue using the existing project ID / route model binding to link a displayed ZF or ZT order number to its owning Auftrag.

## Controllers
- **Modify:** `App\Http\Controllers\Admin\ProjectController` — update user-visible success/error copy to Auftrag terminology; do not rename internal methods, fields, variables, or storage-folder conventions.
- **Modify:** `App\Http\Controllers\HomeController` — update the dashboard module label/description where it refers to core projects.
- **Modify:** `App\Http\Controllers\TimeRecordController` — update user-visible validation/error copy referring to a project.
- **Modify:** `App\Http\Controllers\SchedulerController` — update assignment, change, and cancellation notification copy to Auftrag terminology.
- **Modify:** `App\Http\Controllers\Workflow\ProjectStepController`, `App\Http\Controllers\Admin\Workflow\AssignmentController`, `App\Http\Controllers\Admin\Workflow\StageController`, `App\Services\Workflow\WorkflowService`, and `App\Services\Workflow\StageAdvancer` — update user-visible workflow labels/messages that refer to the core Auftrag.
- **Modify:** `App\Http\Controllers\Admin\SupplierProjectController` — use “Lieferantenauftrag” in user-visible copy where the existing supplier-project record represents the supplier work for an Auftrag; preserve the class, route, and data identifiers.
- **Modify:** `App\Http\Controllers\Admin\ProjectOfferController` — update user-visible offer copy to “Auftragsangebot” where it refers to an offer for a core Auftrag.
- **Modify:** `App\Http\Controllers\Admin\Settings\ProjectSettingsController` and `ProjectServicesController` — update user-visible status/service messages to work-order terminology without changing their actions or data.
- **Modify:** `App\Http\Controllers\Admin\Settings\RoleController` — update the visible module labels for projects and project offers to Auftrag terminology while preserving permission keys.
- **Modify:** `App\Services\AI\AiConversationOrchestrator` — update assistant guidance/examples that refer to core projects.
- **Modify:** `App\Console\Commands\ParseDrillLog` — update operator-facing console output that refers to a core Auftrag.

No controller actions or business logic are added.

## Middleware / Policies
No middleware or policy changes. Keep existing admin and module-access gates. A user must not gain access to the admin detail page through the new link.

## Views
No templates are created; all existing templates retain their current layouts.

- **Modify core admin Auftrag views:** `resources/views/admin/projects/index.blade.php`, `create.blade.php`, `edit.blade.php`, and `show.blade.php`. Replace user-facing “Projekt” labels with appropriate Auftrag terms. On the index, render each present ZF/ZT order number as a link to `route('admin.projects.show', $project)`. Keep the number text unlinked if there is no associated project record. On the detail page, show the ZF and ZT Auftragsnummern clearly as identifiers.
- **Modify related admin views:** `resources/views/admin/activity-timeline/index.blade.php`; `resources/views/admin/bauteile/create.blade.php`, `edit.blade.php`, and `show.blade.php`; `resources/views/admin/home/index.blade.php` and `search.blade.php`; `resources/views/admin/partials/sidebar.blade.php`; `resources/views/admin/project_offers/create.blade.php`, `edit.blade.php`, `index.blade.php`, `items/create.blade.php`, `items/edit.blade.php`, `items/show.blade.php`, and `email_templates/select.blade.php`; `resources/views/admin/projects/offers/index.blade.php` and `show.blade.php`; `resources/views/admin/projects/positions/create.blade.php`, `edit.blade.php`, and `index.blade.php`; `resources/views/admin/projects/projects/create.blade.php`, `edit.blade.php`, `index.blade.php`, and `show.blade.php`; `resources/views/admin/settings/project-service-show.blade.php`, `project-service.blade.php`, `project-status-show.blade.php`, and `project-status.blade.php`; and `resources/views/admin/time/change-logs.blade.php`, `compare-old.blade.php`, `compare.blade.php`, `list.blade.php`, `list_old.blade.php`, `logs_old.blade.php`, `partials/request-card.blade.php`, `record-edit.blade.php`, `show.blade.php`, and `weekly-overview.blade.php`.
- **Modify related user-facing views:** `resources/views/user/home/index.blade.php`, `layouts/index.blade.php`, `printer-problems/__form.blade.php`, `printer-problems/create.blade.php`, `printer-problems/show.blade.php`, `projects/index.blade.php`, `projects/logs.blade.php`, `projects/partials/workflow-card.blade.php`, `scheduler/index.blade.php`, `time_records/create.blade.php`, `time_records/create_old.blade.php`, `time_records/index-old.blade.php`, `time_records/index.blade.php`, `time_records/partials/last-week-table.blade.php`, `time_records/request-form.blade.php`, `time_records/show.blade.php`, `time_records/show_old.blade.php`, `workflow/card.blade.php`, `workflow/index.blade.php`, and `workflow/show.blade.php`; also `resources/views/auth/register.blade.php` and `resources/views/components/assistant-chat.blade.php`.
- Add Auftragsnummer links only on admin-side pages. Do not add links on the public/front-user side.
- Preserve access control: the existing admin detail route and its role/module permissions remain unchanged.
- Translate terminology consistently: “Projektname” becomes “Auftragsbezeichnung”; project status becomes “Auftragsstatus”; project service becomes “Auftragsleistung”; and project workflow becomes “Auftragsworkflow”. Use “Lieferantenauftrag” for the distinct supplier-side work record, not “Auftrag” alone where that would confuse it with the core customer Auftrag.
- Keep company labels (ZF/ZT), order-number values, project/customer-entered names, and statuses unchanged.

## Files to change
- `app/Console/Commands/ParseDrillLog.php`
- `app/Http/Controllers/Admin/ProjectController.php`
- `app/Http/Controllers/Admin/ProjectOfferController.php`
- `app/Http/Controllers/Admin/Settings/ProjectSettingsController.php`
- `app/Http/Controllers/Admin/Settings/ProjectServicesController.php`
- `app/Http/Controllers/Admin/Settings/RoleController.php`
- `app/Http/Controllers/Admin/SupplierProjectController.php`
- `app/Http/Controllers/Admin/Workflow/AssignmentController.php`
- `app/Http/Controllers/Admin/Workflow/StageController.php`
- `app/Http/Controllers/HomeController.php`
- `app/Http/Controllers/SchedulerController.php`
- `app/Http/Controllers/TimeRecordController.php`
- `app/Http/Controllers/Workflow/ProjectStepController.php`
- `app/Services/AI/AiConversationOrchestrator.php`
- `app/Services/Workflow/StageAdvancer.php`
- `app/Services/Workflow/WorkflowService.php`
- The Blade templates enumerated under **Views**.
- `lang/de/workflow.php`, `lang/en/workflow.php`, and `lang/en.json`.
- `routes/web.php` is unchanged unless implementation discovers the existing detail route is unavailable under the `projects` module gate; do not rename existing URLs or route names.

## Files to create
- `.claude/specs/05-auftrag-terminology-linking.md` (this requirement document).
- No application source files.

## New composer packages
No new dependencies.

## Rules for implementation
- Treat this as a user-facing terminology change, not a domain-model/database rename. Keep `Project`, `project_id`, `projects`, `project_name`, `config('modules.projects')`, existing routes, and existing file paths stable.
- Search all user-visible German and English strings across Blade views, controllers, notifications, workflow messages, assistant guidance, and operator-facing output; do not use blind global replacement on identifiers or customer-entered data.
- For core records use German “Auftrag/Aufträge/Auftragsnummer”; use “Auftragsbezeichnung” for the existing project name field. Keep “Lieferantenauftrag” for the separate supplier-side record where that distinction is meaningful.
- Link each available ZF/ZT number in the admin Auftrag list to the existing `admin.projects.show` route using the owning Project record, not by looking up the order-number string. Do not render a broken link for a missing number or a record without an associated Project.
- Preserve access control: only users already authorized to read the admin Auftrag detail page may follow a link to it. Do not add a public show route or change role/module permissions.
- German UI strings first; update the existing English equivalents without introducing a new localization system. Keep existing view layouts.
- Follow MVC conventions and existing project patterns; use parameterized queries only if implementation unexpectedly requires query changes.
- Use Bootstrap 5 and Tailwind v4 utilities already present; do not add a CSS framework or service provider.
- Run Laravel Pint on changed PHP files and targeted Pest tests before completion.

## Definition of done
1. **REQ-001:** The admin Auftrag list and detail pages render “Auftrag/Aufträge/Auftragsnummer” terminology and no longer label the core customer work as a “Projekt”.
2. **REQ-002:** Each populated ZF and ZT number in the admin Auftrag list links to the correct existing admin detail URL for that record; absent numbers are not rendered as broken links.
3. **REQ-003:** The detail-page link remains protected by existing authentication, role, and projects-module permissions; no front-user links or additional access are introduced.
4. **REQ-004:** Time records, scheduling, workflow, Bauteile, supplier-side work, offers, dashboard/search, printer-problem forms, assistant guidance, and notifications use the agreed terminology when referring to the core Auftrag.
5. **REQ-005:** “Lieferantenauftrag” remains distinct from the core Auftrag where the UI refers to a supplier-side work record.
6. **REQ-006:** Existing route names/URLs, database schema, Project relationships, stored names, and storage paths remain unchanged; existing create/edit/search/show flows continue to pass.
7. **REQ-007:** German and English wording is consistent in the touched views and workflow translations.
8. **REQ-008:** A Pest feature test verifies that the admin list links the displayed company order number to the correct `admin.projects.show` route and that existing access controls remain in effect.
9. **REQ-009:** Targeted relevant Pest tests pass and Laravel Pint reports no formatting changes on modified PHP files.
