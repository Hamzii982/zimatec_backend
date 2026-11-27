# Spec: CNC Data Upload

## Overview
Add a CNC design-document library to ZiMaTech so administrators can upload multiple named PDF, JPG/JPEG, or XML files for each Position or Bauteil and associate each document with one or more Werkzeug/Material entries from the Lager material catalogue. Each associated entry can carry its number in the file and notes. The public project frontend will let workshop users open a project, select a Position or Bauteil, and view its available documents and most recent upload; a Bauteil document may optionally also reference a Position in the same project. This supports shop-floor identification of the correct design file and the tools/materials it describes.

## Module flag
`projects` is the owning module and is already enabled in `config/modules.php`; keep it enabled and guard the feature routes with it. The material search uses the existing `Material` records from the `tablar` module, which is also enabled; no flag changes are required.

## Depends on
- Existing `projects`, `positions`, and `bauteile` tables and their project relationships.
- Existing `Material` model and `materials` table, including `name`, `code`, `description`, `is_werkzeug`, `is_active`, and Lager relationships.
- Existing public project listing at `GET /projects/index`.
- Existing admin route group protected by `auth`, `role:admin`, and module middleware.
- Public viewing, printing, and design-file downloads; CNC upload and material-search actions are admin-only.
- Public Lager reservation, return, and damage actions are available from the CNC page, matching the existing public Lager stock endpoints.
- No direct project-level document uploads. Project recency is aggregated from its Positions' and Bauteile' documents.

## Routes

Public routes, registered only when `config('modules.projects')` is enabled:
- `GET /projects/{project}/cnc-data` (`projects.cnc-data`) — show a project and allow selection of its Positions or Bauteile, with their CNC documents and latest-upload information — `public`.
- `GET /projects/{project}/cnc-data/{fraesdata}/print` (`projects.cnc-data.print`) — show a print-ready preview and open the browser print dialog for the supported design file after confirming it belongs to the project — `public`.
- `GET /projects/{project}/cnc-data/{fraesdata}/download` (`projects.cnc-data.download`) — view/download a supported design file after confirming the document belongs to the project — `public`.

Admin routes, inside the existing `auth` + `role:admin` group and guarded by `config('modules.projects')` plus `module:projects,write`:
- `GET /admin/projects/{project}/fraesdata/create` (`admin.fraesdata.create`) — show the upload form; accepts a target `position_id` or `bauteil_id`, and an optional `related_position_id` for a Bauteil — `admin`.
- `POST /admin/projects/{project}/fraesdata` (`admin.fraesdata.store`) — save a supported design file and its Werkzeug/Material rows — `admin`.
- `GET /admin/fraesdata/materials/search` (`admin.fraesdata.materials.search`) — search existing materials by name, description, or article number (`code`) for the upload form — `admin`.

Public Lager stock actions, matching the existing unauthenticated Lager workflow:
- `POST /lager/{lager_id}/tablar/reserve` (`tablar.reserve`) — reserve exactly one available unit from the CNC material detail modal.
- `POST /lager/{lager_id}/tablar/reserve/release` (`tablar.reserve.release`) — return one unit from the material's aggregate reserved quantity to available stock.
- `POST /lager/{lager_id}/tablar/reserve/damage` (`tablar.reserve.damage`) — write off one unit from the aggregate reserved quantity as damaged.

Reservations remain aggregated by Material using `quantity` and `on_hold_quantity`; they are not linked to an individual CNC row, document, or user. The CNC page must label the held quantity as a material-wide total. Returning or writing off stock changes only the requested quantity and must not settle or clear other held units.

On `/lager/{lager_id}/tablar`, clicking a material with a reservation still opens the regular stock-action modal with Einlagern, Entnehmen, and Reservieren. The modal shows the held amount and offers a separate link to open the existing reservation-management modal; it must not open the settlement modal automatically.

## Database changes
Create one migration, after checking the existing migration directory for duplicate table names:
- `2026_11_26_153700_create_fraesdata_and_fraesdata_tools_tables.php`

Create `fraesdata` with:
- `id`, required `project_id` foreign key (`projects`, cascade on delete).
- Nullable `position_id` foreign key (`positions`, cascade on delete) and nullable `bauteil_id` foreign key (`bauteile`, cascade on delete). Exactly one is the upload target; application validation enforces this. For a Bauteil target, `position_id` is the optional same-project Position association. A Position target does not have a Bauteil target.
- Nullable `related_position_id` foreign key (`positions`, null on delete), used only for the optional Position association on a Bauteil-target document.
- Required `name`, required `document_path`, nullable `uploaded_by` foreign key (`users`, null on delete), and timestamps.
- Indexes supporting project, Position, and Bauteil recent-document lookups.

Create `fraesdata_tools` with:
- `id`, required `fraesdata_id` foreign key (`fraesdata`, cascade on delete), required `material_id` foreign key (`materials`, restrict on delete).
- Nullable `tool_number` for the Werkzeuge/material identifier printed in the file and nullable `notes`.
- Timestamps and indexes for parent/material lookups. Do not enforce a unique `(fraesdata_id, material_id)` pair; the same material may appear more than once in a document with different file numbers.

Store PDF, JPG/JPEG, and XML files on a non-public storage disk and serve them through the project-scoped download route with the correct filename extension and media type. Do not expose raw storage paths in public markup.

Extend the `material_consumption.consumption_type` enum with `damaged` so permanently written-off held stock is recorded in the existing inventory history. Damage decreases `on_hold_quantity` without increasing `quantity`; retain the Material catalogue record and its CNC references.

## Eloquent models
- **Create:** `App\Models\Fraesdata`
  - Explicitly use the `fraesdata` table.
  - `belongsTo` Project, target Position, target Bauteil, related Position, and uploader User; `hasMany` FraesdataTool.
  - Cast timestamps normally; expose the original document filename only if needed for display, while `name` remains the user-provided document title.
- **Create:** `App\Models\FraesdataTool`
  - Explicitly use `fraesdata_tools`; `belongsTo` Fraesdata and Material.
- **Modify:** `App\Models\Project` — add a `hasMany` relationship for its CNC data records.
- **Modify:** `App\Models\Position` — add a `hasMany` relationship for documents targeting the Position.
- **Modify:** `App\Models\Bauteil` — add a `hasMany` relationship for documents targeting the Bauteil.
- **Modify:** `App\Models\Material` — add a `hasMany` relationship to FraesdataTool.
- **Modify:** `App\Models\User` — add a `hasMany` relationship for CNC documents uploaded by the user.

## Controllers
- **Create:** `App\Http\Controllers\Admin\FraesdataController`
  - `create(Project $project)` — validate the requested target belongs to the route project, load the optional same-project Position choices for Bauteil targets, and render the upload form.
  - `store(StoreFraesdataRequest $request, Project $project)` — validate the target, supported design file, title, and child material/tool rows; persist the document and child rows transactionally; store the uploaded file on non-public storage.
  - `searchMaterials(Request $request)` — validate a bounded search term and return matching existing materials by name, description, or article number; return only the material name and article number for display.
- **Create:** `App\Http\Controllers\FraesdataController`
  - `projectIndex(Project $project)` — show Positions and Bauteile with document lists/latest upload metadata, respecting an optional selected target.
  - `download(Project $project, Fraesdata $fraesdata)` — ensure the document belongs to the requested project and stream the supported design file with its correct extension and media type without exposing its storage path.
  - `printDocument(Project $project, Fraesdata $fraesdata)` — scope the document to the project and render a print-ready PDF/image/XML preview that opens the browser print dialog.
- **Create:** `App\Http\Requests\StoreFraesdataRequest` — validate title, PDF/JPG/JPEG/XML upload (maximum 20 MB), exactly one target, target/project ownership, optional related Position ownership, and an array of existing `materials.id` values with nullable tool numbers and notes.
- **Modify:** `App\Http\Controllers\ProjectController@index` — include the latest associated CNC document timestamp for each project without introducing an N+1 query.
- **Modify:** `App\Http\Controllers\Admin\ProjectController@show` — load the recent document for each Position and Bauteil so admin cards can link to the upload form and show upload recency without per-card queries.

## Middleware / Policies
No new middleware or policy classes. Keep all CNC uploads and material search behind existing `auth` + `role:admin` middleware and the projects write gate. Project document listing and download are intentionally public, as requested; the download controller must scope the document to the route-bound project and return 404 for mismatches.

## Views
- **Create:** `resources/views/admin/fraesdata/create.blade.php` — extends `admin.layouts.index`; ZT-style German-first design-file/title form headed "Neues Dokument", showing project name/order numbers and an explicit Position/Bauteil target, searchable Material/Werkzeug selector, per-entry file number and notes, plus the optional Position selector for Bauteil uploads.
- **Create:** `resources/views/user/projects/cnc-data.blade.php` — extends `user.layouts.index`; shows project context, selectable Position and Bauteil cards, document titles, material/tool rows, latest-upload badges, public download links, and print buttons.
- **Modify:** `resources/views/user/projects/cnc-data.blade.php` — make each material/tool entry clickable and open a ZT-styled modal showing Lager, Tablar/Fach, available quantity, and aggregate reserved quantity, with one-unit reserve, return, and confirmed damage actions.
- **Create:** `resources/views/user/projects/cnc-data-print.blade.php` — print-ready view for supported files, with browser print controls and an automatic print dialog.
- **Modify:** `resources/views/admin/projects/show.blade.php` — add a German CNC upload button to every Position and Bauteil card, passing the correct target; display its most recent design-file upload time when present.
- **Modify:** `resources/views/user/projects/index.blade.php` — make each project card link to its CNC data page and display the latest upload time across that project's Positions and Bauteile when available.
- Add German and English strings in `lang/de/fraesdata.php` and `lang/en/fraesdata.php`.
- Project, Position, and Bauteil create/update actions return users to the relevant project detail page.

## Files to change
- `routes/web.php`
- `app/Models/Project.php`
- `app/Models/Position.php`
- `app/Models/Bauteil.php`
- `app/Models/Material.php`
- `app/Models/User.php`
- `app/Http/Controllers/ProjectController.php`
- `app/Http/Controllers/Admin/ProjectController.php`
- `app/Http/Controllers/TablarController.php`
- `app/Models/MaterialConsumption.php`
- `resources/views/admin/projects/show.blade.php`
- `resources/views/user/projects/index.blade.php`

## Files to create
- `database/migrations/2026_11_26_153700_create_fraesdata_and_fraesdata_tools_tables.php`
- `app/Models/Fraesdata.php`
- `app/Models/FraesdataTool.php`
- `app/Http/Controllers/Admin/FraesdataController.php`
- `app/Http/Controllers/FraesdataController.php`
- `app/Http/Requests/StoreFraesdataRequest.php`
- `resources/views/admin/fraesdata/create.blade.php`
- `resources/views/user/projects/cnc-data.blade.php`
- `resources/views/user/projects/cnc-data-print.blade.php`
- `lang/de/fraesdata.php`
- `lang/en/fraesdata.php`
- `tests/Feature/FraesdataTest.php`
- `database/migrations/2026_11_27_090000_add_damaged_to_material_consumption_type_enum.php`

## New composer packages
No new dependencies.

## Rules for implementation
- Follow MVC: routes → controllers (Form Requests for validation) → Eloquent models → Blade views.
- Use route model binding where possible; scope documents to the route-bound project on public downloads.
- All admin routes must remain wrapped in `auth` and `role:admin`; apply the existing `module:projects,write` middleware to upload and material-search routes.
- Check `config('modules.projects')` before registering project-scoped feature routes. Do not change module defaults.
- Use Laravel Pint style (`./vendor/bin/pint`) before commit.
- Parameterised Eloquent queries only — no raw SQL strings with user input. Search material `name` and `description` with escaped/bound query parameters.
- German UI strings first; add corresponding English translations.
- Use Bootstrap 5 + Tailwind v4 utilities already in the project; do not introduce a new CSS framework.
- No new service providers unless absolutely required.
- Accept PDF, JPG/JPEG, and XML files up to 20 MB, and store them on non-public storage. Public downloads must go through the scoped controller route.
- Validate the document target belongs to the selected project. For a Bauteil document, any selected Position must belong to the same project; Position-target documents cannot also specify a Bauteil or related Position.
- Use transactions for document metadata and child material rows; clean up a stored file if database persistence fails.
- Do not add direct project uploads. Project latest-upload indicators are derived from associated Position and Bauteil documents.
- Material rows refer to existing `materials` records; store the optional file Werkzeug number and notes on `fraesdata_tools`. Search by name, description, or code, but display only name and code.
- CNC Lager actions use the existing aggregate reservation model: reserve one unit at a time; release or write off only the requested held quantity under a row lock; never delete the Material catalogue record. Keep the aggregate nature visible in the modal.
- CNC stock actions remain public, matching the existing `/lager/{lager_id}/tablar` write endpoints. Damage is confirmed in the UI and recorded as `damaged`.

## Definition of done
1. `php artisan route:list` shows the public project CNC page/print/download and admin create/store/material-search routes only when the projects module is enabled.
2. The migration creates `fraesdata` and `fraesdata_tools` without colliding with existing tables; foreign keys and delete behavior match this spec.
3. An admin can upload a named PDF, JPG/JPEG, or XML file for a Position, add multiple Material/Werkzeug rows with optional file numbers and notes, and see the persisted record on that Position.
4. An admin can upload a named PDF, JPG/JPEG, or XML file for a Bauteil and may associate it with a Position from the same project; a Position from another project is rejected.
5. Uploading an unsupported or oversized file, a missing target, or conflicting targets returns validation errors and does not leave orphaned files or rows.
6. Material search returns matches on name, description, or article number and displays only name/article number; it does not accept a material ID that does not exist.
7. Admin upload and material-search actions reject unauthenticated and non-admin users.
8. The public projects index links to each project's CNC data page and shows the latest associated upload when present.
9. The public CNC page allows selecting a Position or Bauteil and displays its documents and associated material/tool details; the project-level recent timestamp includes documents from both entity types.
10. Public download works for a document belonging to the selected project and returns 404 for a document belonging to another project.
11. `php artisan test --filter=FraesdataTest` passes Pest tests covering the new controller actions, validation, public visibility, admin-only mutations, cross-project scoping, and material search.
12. `./vendor/bin/pint` reports no formatting changes for modified PHP files.
13. Project, Position, and Bauteil create/update actions redirect to the relevant project detail page.
14. Users can open a print-ready page for PDF, JPG/JPEG, or XML documents, invoke browser printing, and cannot print a document through a different project URL.
15. Clicking a CNC Material/Werkzeug opens a modal with its Lager and Tablar/Fach, available stock, and aggregate reserved stock; the table uses a narrow Werkzeugnummer column and a wider Material/Werkzeug column with an article-number badge.
16. Public users can reserve one unit, return one held unit, or confirm writing off one held unit as damaged; each action updates only that amount and reports the resulting counts.
17. Damaged stock is removed from inventory totals and logged as `damaged` without deleting the Material catalogue record or CNC history.
18. On the Lager page, reserved materials open the regular stock-action modal first; a separate link opens reservation management when held stock exists.
