# Implementation Plan: ERP Auftrag Terminology and Number Links

**Date**: 2026-11-26 | **Spec**: `.claude/specs/05-auftrag-terminology-linking.md`

## Summary

Update user-facing labels and messages for the existing core customer-work record to use Auftrag terminology throughout connected ERP modules, without changing internal Project identifiers or behavior. Add admin-only links from ZF/ZT order numbers in the master Auftrag list to the existing `admin.projects.show` route. Cover the link/access behavior with a focused Pest feature test, update German and English copy, then audit remaining visible wording and run targeted validation.

## Technical Context

**Language/Version**: PHP 8.2+, Laravel 12  
**Primary Dependencies**: Laravel Blade, Laravel routing/authentication, Pest, Laravel Pint; no new dependencies  
**Storage**: Existing MySQL schema and existing Project/order-number records; no schema changes  
**Testing**: Pest feature tests using the configured MySQL test database  
**Target Platform**: Server-rendered Laravel web application, PWA, and Electron shell  
**Performance Goals**: No measurable runtime or query changes; the list links use the already-loaded Project model  
**Constraints**: German-first wording; preserve Project classes, database identifiers, route names/URLs, permissions, stored names, and storage paths. Links are admin-side only.

## Constitution Check

- **Existing architecture**: PASS — keeps the existing Laravel MVC, `Project` model, Blade views, and route model binding.
- **Compatibility**: PASS — no database, API, route-name, URL, or storage-path changes.
- **Authorization**: PASS — reuses the existing admin show route and does not add front-user links or grant access.
- **Localization**: PASS — updates German-first UI copy and the existing English translation resources.
- **Scope**: PASS — terminology and admin navigation only; no business logic or data migration.
- No standalone `constitution.md` exists in the repository; project conventions are taken from `CLAUDE.md` and the approved feature spec.

## Applied Guidelines

- No matching framework migration guideline applies. This feature does not migrate frameworks or transform source languages.
- Follow repository Laravel/Blade conventions and run Pint, as specified by `CLAUDE.md`.

## Implementation Steps

### Step 1.1: [Cross-cutting] Add link and access regression coverage
- **Requirements**: REQ-002, REQ-003, REQ-008
- **Design inputs**: Approved spec; existing `admin.projects.show` route and admin route middleware in `routes/web.php`.
- **Description**: Add a Pest feature test that fails before the link change, checks both available company order numbers target the owning Project's existing admin show URL, checks missing company numbers do not produce broken anchors, and verifies the existing route remains protected. Do not add front-user links.

### Step 2.1: [US1] Update core admin Auftrag pages and link numbers
- **Requirements**: REQ-001, REQ-002, REQ-003, REQ-006
- **Design inputs**: Existing Project list/show views and route model binding.
- **Description**: Update user-visible terminology in the core admin list/create/edit/show views. Render each present ZF/ZT number in the index as a link to `admin.projects.show` using the owning Project instance. Keep the existing route and access rules unchanged; do not link unrelated supplier-side projects as core Aufträge.

### Step 2.2: [US1] Update related admin-side terminology
- **Requirements**: REQ-001, REQ-004, REQ-005, REQ-006
- **Design inputs**: Existing connected admin views and module access patterns.
- **Description**: Update related admin-page copy, including the activity timeline and offer-template selection, while preserving technical identifiers, data values, routes, and access conditions. Use Lieferantenauftrag for the distinct supplier-side records.

### Step 3.1: [US2] Update connected user-facing terminology
- **Requirements**: REQ-004, REQ-006
- **Design inputs**: Existing user-facing views and visibility constraints.
- **Description**: Update connected user-facing copy, but do not add Auftragsnummer links to public/front-user pages.

### Step 3.2: [US2] Update controller messages and translations
- **Requirements**: REQ-004, REQ-005, REQ-006, REQ-007
- **Design inputs**: Existing admin/user views, workflow translation files, and controller/service messages identified in the approved spec.
- **Description**: Replace user-facing wording for the core customer Auftrag across connected views, notifications, validation/flash copy, permission labels, settings messages, assistant guidance, and operator-facing output. Use “Lieferantenauftrag” for distinct supplier-side records. User-facing views may receive terminology updates but must not gain Auftragsnummer links. Update existing German and English translation resources without changing technical identifiers.

### Step 4.1: [Cross-cutting] Audit terminology and compatibility
- **Requirements**: REQ-003, REQ-004, REQ-005, REQ-006, REQ-007
- **Design inputs**: Approved spec and repository-wide source search.
- **Description**: Search user-visible strings for stale references to the core Project concept; review exceptions such as internal identifiers and customer-entered values. Confirm no schema, route, or permission changes.

### Step 4.2: [Cross-cutting] Run tests and formatting validation
- **Requirements**: REQ-003, REQ-004, REQ-005, REQ-006, REQ-007, REQ-009
- **Design inputs**: Approved spec and repository-wide source search.
- **Description**: Run the new feature test, relevant existing project/workflow tests, and Pint on modified PHP files. Confirm the new admin link resolves correctly and existing behavior remains.

## Project Structure

```text
app/
  Console/Commands/ParseDrillLog.php
  Http/Controllers/
    Admin/ProjectController.php
    Admin/ProjectOfferController.php
    Admin/SupplierProjectController.php
    Admin/Settings/{ProjectSettingsController,ProjectServicesController,RoleController}.php
    Admin/Workflow/{AssignmentController,StageController}.php
    HomeController.php
    SchedulerController.php
    TimeRecordController.php
    Workflow/ProjectStepController.php
  Services/
    AI/AiConversationOrchestrator.php
    Workflow/{StageAdvancer,WorkflowService}.php
resources/views/
  admin/                         # terminology updates; core project index links
  user/                          # terminology only; no new links
  auth/register.blade.php
  components/assistant-chat.blade.php
lang/
  de/workflow.php
  en/workflow.php
  en.json
tests/Feature/ProjectTerminologyTest.php
.claude/
  specs/05-auftrag-terminology-linking.md
  plans/05-auftrag-terminology-linking/
    plan.md
    checkpoints/{spec-to-plan,plan-to-tasks,tasks-to-impl}.yaml
    batch-report.yaml
```

## Testing Strategy

- **appType**: Server-rendered HTML with Laravel controllers and Blade views.
- **Critical user journeys**:
  1. Admin opens the Auftrag list and follows either populated ZF or ZT number to the correct existing detail page.
  2. A missing order number is not rendered as a broken link; other detail-page actions remain available.
  3. An unauthorised user gains no access to the admin detail page, and user-facing pages do not gain order-number links.
  4. Core labels and connected-module copy use the approved German/English terminology while data and identifiers remain unchanged.
- **primaryValidationStack**: Pest feature tests, targeted existing Pest tests, Blade rendering through the Laravel test client, Laravel Pint.
- **fallbackMatrix**:
  - `infra-tier`: Configured MySQL test database → no fallback planned because query/database behavior is unchanged.
  - `browser-tier`: Laravel feature tests against rendered HTML → manual browser smoke test if UI rendering issues remain; no JavaScript behavior is introduced.
- **Environment requirements**: PHP/Composer dependencies and configured `zimatech_test` MySQL database.
- **knownGaps**: Feature tests validate rendered links and authorization, not visual styling in every screen size.
- **Test data strategy**: Use isolated factory records under `RefreshDatabase`; create a core Project with populated and missing company order-number variants.
- **Acceptance criteria**: Correct link URLs and number labels; no broken anchor for absent numbers; existing admin gate remains; no front-user links; target tests and Pint pass.
- **Validation review expectations**: Check that the only link behavior change is in admin-side core Auftrag lists, and audit all changed visible strings against the approved terminology.

## Complexity Tracking

No constitution violations or additional architectural complexity.

## Tasks

### Phase 1: Foundational
- [x] T001 [Plan:1.1] Add `tests/Feature/ProjectTerminologyTest.php` to verify ZF/ZT number links target the existing detail route, absent numbers are not linked, and unauthorised requests remain denied.

### Phase 2: User Story 1 — Admin Auftrag list and detail
- [x] T002 [US1] [Plan:2.1] Update `resources/views/admin/projects/index.blade.php`, `create.blade.php`, `edit.blade.php`, and `show.blade.php` with Auftrag terminology; link only populated ZF/ZT order numbers in the admin index to `admin.projects.show`.
- [x] T003 [US1] [Plan:2.2] Update admin navigation, search, dashboard, Bauteile, project-offer, supplier-project, position, settings, and time views listed in `.claude/specs/05-auftrag-terminology-linking.md`; retain existing routes, data, and access conditions.

### Phase 3: User Story 2 — Connected modules and consistent copy
- [x] T004 [US2] [Plan:3.1] Update user-facing home, layout, printer-problem, project, scheduler, time-record, and workflow views listed in `.claude/specs/05-auftrag-terminology-linking.md` to use the approved terminology only; do not add Auftragsnummer links to public/front-user pages.
- [x] T005 [US2] [Plan:3.2] Update user-visible copy in the specified controllers, workflow services, assistant guidance, and drill-log command; update `lang/de/workflow.php`, `lang/en/workflow.php`, and `lang/en.json`, preserving technical names and supplier-order distinctions.

### Phase 4: Polish and validation
- [x] T006 [Plan:4.1] Search user-visible source for stale core-Project wording and correct only user-facing copy; retain internal identifiers, customer-entered text, and unrelated meanings.
- [x] T007 [Plan:4.2] Run `php artisan test --filter=ProjectTerminologyTest`, `php artisan test --filter=ProjectWorkflowTest`, and `./vendor/bin/pint --test` on modified PHP files; report outcomes and verify the diff contains no database or route changes.

## Requirement Mapping

| REQ ID | Description | Plan Items | Implementation Evidence |
|--------|-------------|------------|------------------------|
| REQ-001 | Core admin pages use Auftrag terminology | 2.1, 2.2 | `resources/views/admin/projects/*.blade.php` and related admin views |
| REQ-002 | Populated company order numbers link to the correct detail page | 1.1, 2.1 | `resources/views/admin/projects/index.blade.php`, `tests/Feature/ProjectTerminologyTest.php` |
| REQ-003 | Admin-only link scope and existing access control are preserved | 1.1, 2.1, 4.1, 4.2 | Admin view condition, existing route middleware, feature test |
| REQ-004 | Connected module text uses Auftrag terminology | 2.2, 3.1, 3.2, 4.1 | Listed related views, controllers, and services |
| REQ-005 | Supplier-side work retains its distinct Lieferantenauftrag label | 2.2, 3.2, 4.1 | `SupplierProjectController.php` and supplier-project views |
| REQ-006 | Existing route, schema, model, data, and storage contracts remain stable | 2.1, 2.2, 3.1, 3.2, 4.1 | Diff review and existing regression tests |
| REQ-007 | German and English copy is consistent | 3.2, 4.1 | Workflow translation files and `lang/en.json` |
| REQ-008 | Pest covers number links and access behavior | 1.1 | `tests/Feature/ProjectTerminologyTest.php` |
| REQ-009 | Targeted tests and Pint pass | 4.2 | Test and Pint command results |
