# Plan Research: CNC Data Upload

## Resolved spec-level ambiguity

### Target Position versus Bauteil-associated Position

- **Decision:** `position_id` identifies a Position-target document only. `bauteil_id` identifies a Bauteil-target document. `related_position_id` is the optional Position association for a Bauteil-target document and must belong to the same project.
- **Rationale:** The spec's database section describes `position_id` as both a target and an optional Bauteil association, then separately defines `related_position_id` for that same association. Its routes and controller section consistently use `related_position_id` as the optional Bauteil association. Keeping the two roles distinct makes target validation and Eloquent relations unambiguous.
- **Alternatives considered:** Reuse `position_id` for both roles and remove `related_position_id`; rejected because the same foreign key would represent different concepts and conflict with the controller contract. Use a polymorphic target; rejected because the spec explicitly asks for Position/Bauteil foreign-key relations and already names both target columns.

## Requirement identifiers

- **Decision:** Use `REQ-001` through `REQ-012` in the plan/checkpoints as stable labels for the spec's numbered Definition-of-done criteria 1 through 12.
- **Rationale:** The feature spec has 12 independently testable acceptance criteria but no literal `REQ-XXX` identifiers. The planning workflow requires end-to-end requirement traceability. This preserves one-to-one coverage without changing the user-approved spec.
- **Alternatives considered:** Leave requirements unnumbered; rejected because the mandatory requirement mapping and traceability checkpoints would be empty. Edit the spec to insert IDs; rejected because the request is to produce a plan and the numbered criteria already provide stable source anchors.

## Frontend material search integration

- **Decision:** Keep the search interaction in a page-scoped script pushed through `@stack('scripts')` from `resources/views/admin/fraesdata/create.blade.php`.
- **Rationale:** The existing admin layout exposes `@stack('scripts')`, while the spec does not request another JavaScript bundle or build entry. This allows the AJAX search endpoint to be used without adding dependencies or an unlisted asset pipeline change.
- **Alternatives considered:** Add a new Vite entry and update app imports; rejected as unnecessary for this one page and not called for by the spec.

## Repository context and missing artifacts

- No standalone `constitution.md`, `knowledge-graph.json`, `project-topology.md`, or `skills/guidelines/` directory was found in the repository.
- No separate architecture/data-model/API design artifacts were listed as dependencies for this planning task. The plan uses the approved feature spec and repository conventions in `CLAUDE.md`; it does not generate upstream design artifacts.
- Since no project topology exists, implementation tasks do not carry G-group labels.
