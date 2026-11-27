# Implementation Plan: CNC Data Upload

**Date**: 2026-11-26 | **Spec**: `.claude/specs/06-cnc-data-upload.md`

## Summary

Implement a project-scoped CNC design-file library using the existing Laravel 12 MVC stack. Admins upload PDF, JPG/JPEG, or XML files against a Position or Bauteil and associate each with one or more existing Materials, including the Werkzeug number from the file and notes. A public project page lets workshop users select a Position or Bauteil, inspect its documents and latest-upload time, view a design file, or open a print-ready preview through project-scoped endpoints. Keep uploads and material search admin-only, keep project/document viewing public as requested, store files on non-public storage, and verify behavior with Pest.

## Technical Context

**Language/Version**: PHP 8.2+, Laravel 12
**Primary Dependencies**: Laravel routing, Eloquent, Blade, validation, filesystem, Pest, Laravel Pint; no new dependencies
**Storage**: Existing MySQL database; design files stored on a non-public Laravel disk and served by a scoped controller
**Testing**: Pest feature tests with the configured MySQL `zimatech_test` database; `Storage::fake()` for design-file persistence
**Target Platform**: Server-rendered Laravel web app used in browsers, PWA, and Electron shop-floor terminals
**Performance Goals**: Avoid N+1 document lookups on project and entity cards; eager-load document/material data and use indexed project/target/time lookups
**Constraints**: `projects` and `tablar` flags remain unchanged; all admin mutations use existing `auth`, `role:admin`, and `module:projects,write` gates; design files are public only through scoped routes; German-first UI and English translations; preserve existing project/material behavior

## Constitution Check

- **Existing architecture**: PASS — use existing Laravel MVC, Eloquent, Blade, resource storage, and `routes/web.php`.
- **Authorization and exposure**: PASS — upload/search are admin-only; public reads/downloads are explicitly specified and downloads are scoped to the route project.
- **Data integrity**: PASS — enforce project/entity ownership in validation and persist document metadata with its material rows transactionally.
- **Localization and UI**: PASS — German-first strings with corresponding English translations; use existing Bootstrap/Tailwind utilities.
- **Scope and compatibility**: PASS — no new packages, service providers, or module defaults; existing project and Lager models are extended additively.
- No standalone `constitution.md` exists in the repository. This check uses repository guidance in `CLAUDE.md` and the approved feature spec.

## Applied Guidelines

- No matching migration/transformation guideline applies; this is an additive Laravel feature, not a framework migration.
- Follow repository Laravel/Blade conventions, feature-flag route registration, admin middleware, Pest, and Pint as documented in `CLAUDE.md`.
- The existing admin layout provides `@stack('scripts')`; use a page-scoped script for the material search UI instead of introducing a JavaScript dependency or build entry.

## Implementation Steps

### Phase 1: Foundational data layer

#### Step 1.1: Create CNC document schema and Eloquent relationships
- **Requirements**: REQ-002, REQ-003, REQ-004
- **Design inputs**: Approved spec; existing `Project`, `Position`, `Bauteil`, `Material`, and `User` models; existing timestamped migrations.
- **Description**: Add `fraesdata` and `fraesdata_tools` in the specified migration. Implement models and additive relationships. Use `position_id` only as the target when the document targets a Position, `bauteil_id` as the target when it targets a Bauteil, and `related_position_id` only for the optional same-project Position association on a Bauteil document. Enforce exactly one target in request validation and add indexes for project/entity recent-document queries.

#### Step 1.2: Define upload validation and persistence boundaries
- **Requirements**: REQ-003, REQ-004, REQ-005
- **Design inputs**: Spec upload limits and the resolved target-column semantics in `research.md`.
- **Description**: Create `StoreFraesdataRequest` rules for title, PDF/JPG/JPEG/XML uploads up to 20 MB, exactly one target, target/project ownership, optional related Position ownership, and existing child material IDs with optional tool numbers and notes. Admin controller persistence must store the file privately, save parent and children transactionally, and remove the stored file if persistence fails.

### Phase 2: User Story 1 — Admin uploads CNC data (Priority: P1)

#### Step 2.1: Implement protected upload and material-search actions
- **Requirements**: REQ-001, REQ-003, REQ-004, REQ-005, REQ-006, REQ-007
- **Design inputs**: Existing admin route group and `projects` module write gate in `routes/web.php`; `Material` fields and Lager relationships.
- **Description**: Add the create, store, and material-search routes under the existing admin group and `config('modules.projects')` guard. Implement `Admin\FraesdataController` to validate route/project targets, provide same-project Position options for Bauteil uploads, persist documents and material rows, and return bounded name/description/article-number search results while exposing only name and code for display. Keep admin authorization and project write middleware on all three endpoints.

#### Step 2.2: Build the German-first upload form and searchable material selector
- **Requirements**: REQ-003, REQ-004, REQ-006
- **Design inputs**: Spec view requirements; existing admin layout and its script stack.
- **Description**: Create `resources/views/admin/fraesdata/create.blade.php` with a ZT-style "Neues Dokument" header, project/order/explicit-target context, design-file formats, repeatable Material/Werkzeug rows, optional file number and notes, and the optional same-project Position selector for a Bauteil target. Use the material-search endpoint from a view-scoped script, allow the same material to be added more than once, and render validation errors explicitly. Add German/English strings in `lang/de/fraesdata.php` and `lang/en/fraesdata.php`.

#### Step 2.3: Connect upload and recent-document status to admin entity cards
- **Requirements**: REQ-003, REQ-004
- **Design inputs**: Existing admin project detail cards and the new entity relationships.
- **Description**: Update `Admin\ProjectController@show` to eager-load each Position's and Bauteil's latest Fraesdata record and update `resources/views/admin/projects/show.blade.php` with a target-specific upload link on every card and a latest-upload indicator where a document exists. Avoid per-card relationship queries.

### Phase 3: User Story 2 — Workshop users browse and view project CNC data (Priority: P1)

#### Step 3.1: Add public project/entity browsing and recent-upload indicators
- **Requirements**: REQ-001, REQ-008, REQ-009
- **Design inputs**: Existing public `ProjectController@index`, project list view, public route conventions, and spec decision that project recency aggregates child documents.
- **Description**: Add the public project CNC data route and `FraesdataController@projectIndex`. Update the public project listing to link each project and show its latest associated Position/Bauteil upload time. The project CNC page must let visitors select a Position or Bauteil, show that target's documents and material/tool rows, display per-target recency, and reject selected targets that do not belong to the route project. Use eager loading or aggregate queries to avoid N+1 lookups.

#### Step 3.2: Serve and print public design files through project-scoped routes
- **Requirements**: REQ-001, REQ-010
- **Design inputs**: Spec public visibility decision and Laravel private storage.
- **Description**: Add public download and print-preview routes. Confirm the Fraesdata record belongs to the route-bound project, stream the supported design file from non-public storage with its correct extension and media type, and return 404 for mismatched projects or missing files. The print page previews PDF/image/XML content and opens the browser print dialog; never render filesystem paths into the page.

### Phase 4: Cross-cutting validation and polish

#### Step 4.1: Complete feature regression coverage
- **Requirements**: REQ-005, REQ-006, REQ-007, REQ-008, REQ-009, REQ-010, REQ-011
- **Design inputs**: All three feature journeys and the acceptance checklist in the spec.
- **Description**: Complete `tests/Feature/FraesdataTest.php` coverage for public/admin access boundaries, uploads for both target types, multiple child rows, optional same-project Position association, supported/unsupported file types and size validation, search by name/description/code, project and target recency, selected-target scoping, controlled downloads, and cleanup when persistence fails. Run existing project-related tests to catch regressions.

#### Step 4.2: Verify route gating, formatting, and final behavior
- **Requirements**: REQ-001, REQ-011, REQ-012
- **Design inputs**: Final route definitions, tests, and changed PHP files.
- **Description**: Verify the new routes are registered inside the projects module guard and admin write middleware, run the focused Pest suite and relevant existing project tests, and run Pint on changed PHP files. Smoke-test the upload/search UI and public selection/download flow in a browser.

### Phase 5: Follow-up behavior and format support

#### Step 5.1: Align project redirects with the project detail workflow
- **Requirements**: REQ-013
- **Description**: Redirect project create/update and Position/Bauteil create/update actions to the relevant project detail page. For Bauteil updates, use the newly assigned project ID.

#### Step 5.2: Verify expanded design-file formats and polished upload/search UI
- **Requirements**: REQ-003, REQ-004, REQ-005, REQ-006, REQ-009, REQ-010
- **Description**: Accept and serve PDF/JPG/JPEG/XML files with correct MIME types; replace PDF-only wording and icons, update recency/no-file labels, style the upload form with ZT conventions, and display material name plus article number while searching by name, description, or code.

#### Step 5.3: Add a scoped print experience and recency badges
- **Requirements**: REQ-009, REQ-010, REQ-014
- **Description**: Show the latest project document timestamp in a clear badge, provide a print action beside file opening, and render a scoped print preview for each supported format.

### Phase 6: CNC material Lager interactions

#### Step 6.1: Add partial aggregate reservation release and damage handling
- **Requirements**: REQ-016, REQ-017
- **Description**: Extend the existing shared Material hold workflow with row-locked partial return and damage actions; record damage in the consumption ledger without deleting the Material record.

#### Step 6.2: Add clickable CNC material details and reservation controls
- **Requirements**: REQ-015, REQ-016, REQ-018
- **Description**: Show Lager/Tablar/Fach, available stock, and material-wide held quantity in a modal. Reserve one unit per action and keep aggregate reservation semantics explicit. On the Lager page, keep regular stock actions in the primary modal and open reservation settlement only through a separate management link.

#### Step 6.3: Verify CNC stock lifecycle and UI
- **Requirements**: REQ-015, REQ-016, REQ-017
- **Description**: Add Pest coverage for the public modal and stock endpoints, verify partial updates and damage logging, then run focused feature tests and Pint.

## Project Structure

```text
app/
  Http/
    Controllers/
      FraesdataController.php
      Admin/FraesdataController.php
      Admin/ProjectController.php                 # latest records for admin cards
      ProjectController.php                       # latest project recency
    Requests/StoreFraesdataRequest.php
  Models/
    Fraesdata.php
    FraesdataTool.php
    Project.php
    Position.php
    Bauteil.php
    Material.php
    User.php
database/migrations/
  2026_11_26_153700_create_fraesdata_and_fraesdata_tools_tables.php
resources/views/
  admin/fraesdata/create.blade.php
  admin/projects/show.blade.php
  user/projects/cnc-data.blade.php
  user/projects/index.blade.php
lang/
  de/fraesdata.php
  en/fraesdata.php
tests/Feature/
  FraesdataTest.php
routes/web.php
.claude/
  specs/06-cnc-data-upload.md
  plans/06-cnc-data-upload/
    plan.md
    research.md
    checkpoints/{spec-to-plan,plan-to-tasks}.yaml
```

## Testing Strategy

- **appType**: Mixed server-rendered HTML and a small JSON-backed material search interaction.
- **Critical user journeys**:
  1. Admin uploads a Position design file (PDF/JPG/JPEG/XML) with multiple Material/Werkzeug rows, file numbers, and notes; the document and card recency appear.
  2. Admin uploads a Bauteil PDF with an optional Position from the same project; a foreign-project Position is rejected.
  3. Admin material search matches by either material name or description and only existing IDs can be saved.
  4. A public visitor opens a project, selects a Position or Bauteil, and sees its documents and recent-upload time.
  5. A public visitor opens a document belonging to the project; a document from another project cannot be fetched through that URL.
  6. A public visitor opens a CNC material detail modal and reserves, returns, or writes off one unit while the aggregate held count remains correct.
- **primaryValidationStack**: Pest feature tests through Laravel's HTTP kernel, configured MySQL test database, `Storage::fake()` for private design-file storage, `php artisan route:list`, and Laravel Pint.
- **fallbackMatrix**:
  - `infra-tier`: Configured MySQL `zimatech_test` → no fallback planned; foreign-key and migration behavior must be verified against the supported database.
  - `browser-tier`: Pest assertions for rendered HTML and endpoint behavior → manual browser smoke test for interactive material search and responsive card/form layout.
- **Environment requirements**: PHP/Composer dependencies, configured MySQL test database, writable local filesystem for application use; browser available for final UI smoke test.
- **knownGaps**: HTTP tests do not prove the usability of the asynchronous search interaction or all responsive layouts; cover these with the specified browser smoke test.
- **Test data strategy**: Use `RefreshDatabase` and isolated project/Position/Bauteil/Material factories; fake the non-public storage disk; use small supported-format fixtures and assert stored paths, MIME types, and response filenames.
- **Acceptance criteria**: All 18 numbered criteria in the feature spec pass, including route/module access, FK integrity, admin-only CNC uploads, cross-project rejection, recent-upload display, scoped downloads/printing, aggregate Lager interactions, correct CRUD redirects, Pest, and Pint.
- **Validation review expectations**: Confirm project and optional Position ownership is checked server-side; uploaded design files are not directly web-accessible; public routes expose only intended project documents; no N+1 query is introduced; German and English strings are present.

## Complexity Tracking

No constitution violations or additional architectural complexity.

## Tasks

### Phase 1: Foundational data layer
- [x] T001 [Plan:1.1] Add initial schema and relationship assertions to `tests/Feature/FraesdataTest.php`, covering the two tables, foreign-key behavior, and target/material relationships.
- [x] T002 [P] [Plan:1.1] Create `database/migrations/2026_11_26_153700_create_fraesdata_and_fraesdata_tools_tables.php` with project/target/uploader/material foreign keys, delete behavior, timestamps, and recent-lookup indexes; keep `related_position_id` distinct from target `position_id`.
- [x] T003 [P] [Plan:1.1] Create `app/Models/Fraesdata.php` and `app/Models/FraesdataTool.php`, then add CNC document/tool relationships to `app/Models/Project.php`, `Position.php`, `Bauteil.php`, `Material.php`, and `User.php`.
- [x] T004 [Plan:1.2] Create `app/Http/Requests/StoreFraesdataRequest.php` to validate PDF/JPG/JPEG/XML files up to 20 MB, title, mutually exclusive target IDs, project ownership, optional related Position, and nested existing Material IDs/tool numbers/notes.

### Phase 2: User Story 1 — Admin uploads CNC data
- [x] T005 [US1] [Plan:2.1] Add Pest cases to `tests/Feature/FraesdataTest.php` for admin upload access, successful Position/Bauteil persistence, multiple material rows, invalid targets/materials, and material search by name, description, or code.
- [x] T006 [US1] [Plan:2.1] Register project-guarded admin routes in `routes/web.php` and implement create/store/search actions in `app/Http/Controllers/Admin/FraesdataController.php`, including transactional persistence and stored-file cleanup on failure.
- [x] T007 [US1] [Plan:2.2] Create `resources/views/admin/fraesdata/create.blade.php` with a ZT-style new-document header, supported file/title fields, repeatable Material/Werkzeug entries, optional file number/notes, same-project Position selector for Bauteil uploads, and page-scoped search interaction; add German/English strings.
- [x] T008 [US1] [Plan:2.3] Eager-load latest Position/Bauteil documents in `app/Http/Controllers/Admin/ProjectController.php` and add a create-upload link plus recent-upload indication to every matching card in `resources/views/admin/projects/show.blade.php`.

### Phase 3: User Story 2 — Public project CNC data
- [x] T009 [US2] [Plan:3.1,3.2] Add Pest cases in `tests/Feature/FraesdataTest.php` for public project recency, target selection/scoping, material/tool display, supported-file viewing, and cross-project download 404 behavior.
- [x] T010 [US2] [Plan:3.1] Register the public CNC page route in `routes/web.php`; implement project CNC listing/selection in `app/Http/Controllers/FraesdataController.php` and latest project aggregation in `app/Http/Controllers/ProjectController.php`.
- [x] T011 [US2] [Plan:3.1] Create `resources/views/user/projects/cnc-data.blade.php` and update `resources/views/user/projects/index.blade.php` to link projects, select Positions/Bauteile, display documents/material details, and show project/entity latest-upload times.
- [x] T012 [US2] [Plan:3.2] Implement project-scoped design-file responses with matching extensions/MIME types in `App\Http\Controllers\FraesdataController@download` and register its public route, returning 404 for a document outside the route project.

### Phase 4: Cross-cutting validation and polish
- [x] T013 [Plan:4.1] Complete and run `tests/Feature/FraesdataTest.php` for file type/size failures, database/storage cleanup, role/access restrictions, material name/description/code search, recency aggregation, and target/download project scoping; run relevant existing project feature tests.
- [x] T014 [Plan:4.2] Verify route registration and middleware with `php artisan route:list`, run Pint on changed PHP files, and smoke-test admin upload/search plus public project selection/file viewing in a browser.
- [x] T015 [Plan:5.1] Redirect project, Position, and Bauteil create/update actions to the appropriate project detail page; add redirect regression tests.
- [x] T016 [Plan:5.2] Extend upload/download support to PDF/JPG/JPEG/XML, update badges and labels, apply ZT form styling, and add article-number search/display behavior with regression tests.
- [x] T017 [Plan:5.3] Add latest-document badges and a project-scoped print-ready preview for supported design files, with print buttons and regression tests.
- [x] T018 [Plan:6.1] Add row-locked partial aggregate reservation return and damage actions in `TablarController`, extend the consumption type enum with `damaged`, preserve Material records, and test stock counts/history.
- [x] T019 [Plan:6.2] Make CNC material/tool entries open a modal with Lager and shelf location, available and material-wide reserved counts, one-unit reserve/return/damage actions, and German/English strings; retain the compact number/material table layout.
- [x] T020 [Plan:6.3] Add focused feature coverage for public modal rendering, reserve/release/damage actions and partial-hold validation; run the focused Pest suite, route checks, and Pint.
- [x] T021 [Plan:6.2,6.3] Keep the regular material actions modal primary for reserved stock, add a separate reserved-management link, and verify the modal flow in Pest and a browser.

## Requirement Mapping

The source spec numbers its 18 acceptance criteria but does not assign `REQ-XXX` IDs. This plan uses `REQ-001` through `REQ-018` to map one-to-one to those criteria.

| REQ ID | Description | Plan Items | Implementation Evidence |
|--------|-------------|------------|------------------------|
| REQ-001 | Project-guarded public/admin CNC routes and expected route list | 2.1, 3.1, 3.2, 4.2 | `routes/web.php`, `tests/Feature/FraesdataTest.php` |
| REQ-002 | Create `fraesdata` and `fraesdata_tools` with specified FK/index behavior | 1.1 | `database/migrations/2026_11_26_153700_create_fraesdata_and_fraesdata_tools_tables.php` |
| REQ-003 | Admin can upload supported design files for Positions with multiple Material/Werkzeug rows and see them | 1.1, 1.2, 2.1, 2.2, 2.3, 5.2 | Fraesdata models, Form Request, admin controller/view, project cards, Pest tests |
| REQ-004 | Admin can upload supported design files for Bauteile with an optional same-project Position association | 1.1, 1.2, 2.1, 2.2, 2.3, 5.2 | `Fraesdata`, Form Request, admin upload flow, project cards, Pest tests |
| REQ-005 | Invalid targets/files fail without orphaned records or files | 1.2, 2.1, 4.1 | `StoreFraesdataRequest`, transactional controller persistence, `FraesdataTest.php` |
| REQ-006 | Material search matches name, description, and article number while displaying name/code only | 2.1, 2.2, 4.1, 5.2 | Admin search action, upload form script, `FraesdataTest.php` |
| REQ-007 | Upload and material-search endpoints reject unauthenticated/non-admin users | 2.1, 4.1 | `routes/web.php` middleware, `FraesdataTest.php` |
| REQ-008 | Public project index links to CNC pages and shows latest associated upload | 3.1, 4.1 | `ProjectController@index`, `resources/views/user/projects/index.blade.php`, tests |
| REQ-009 | Public CNC page selects Position/Bauteil and shows docs/materials/recency | 3.1, 4.1 | `FraesdataController@projectIndex`, `resources/views/user/projects/cnc-data.blade.php`, tests |
| REQ-010 | Public download works only when the document belongs to the route project, with its correct extension/MIME | 3.2, 4.1, 5.2 | `FraesdataController@download`, scoped route, `FraesdataTest.php` |
| REQ-011 | Pest covers new actions, access, validation, scoping, and search | 2.1, 3.1, 4.1 | `tests/Feature/FraesdataTest.php` |
| REQ-012 | Pint succeeds on modified PHP files | 4.2 | Pint validation result for changed PHP files |
| REQ-013 | Project, Position, and Bauteil create/update redirects target the relevant project detail page | 5.1 | Admin CRUD controllers and `ProjectRedirectTest.php` |
| REQ-014 | Users can print supported design files through a project-scoped print preview | 3.2, 5.3 | `FraesdataController@printDocument`, print view, scoped route, and `FraesdataTest.php` |
| REQ-015 | CNC material rows expose location and aggregate stock details in a clickable modal | 6.2, 6.3 | `FraesdataController@projectIndex`, CNC data view, and `FraesdataTest.php` |
| REQ-016 | Public CNC actions reserve, return, or damage only the requested held quantity | 6.1, 6.2, 6.3 | `TablarController`, public Lager routes, CNC modal script, and `FraesdataTest.php` |
| REQ-017 | Damaged stock is logged and removed from inventory without deleting the Material catalogue record | 6.1, 6.3 | Damaged enum migration, `MaterialConsumption`, `TablarController`, and `FraesdataTest.php` |
| REQ-018 | Reserved materials on the Lager page retain regular stock actions with an optional secondary settlement modal | 6.2, 6.3 | `TablarController@index`, `resources/views/user/tablar/index.blade.php`, `public/js/user/tablar/index.js`, and `TablarFilterTest.php` |
