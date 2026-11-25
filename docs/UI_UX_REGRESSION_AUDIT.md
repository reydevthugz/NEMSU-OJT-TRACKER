# NEMSU OJT Tracker UI and Functionality Regression Audit

## Scope and Git evidence

The active `ProgressDevelopment` branch (`825b3f6`) is based directly on the Phase 2 commit (`00f9c3e`). It is not a descendant of the local Phase 3 branches. The repository retains two relevant, separate implementations:

- `aff846a` (`main`): administrator dashboard, seven management areas, `AdminController`, `AdminModel`, and `tests/admin.php`.
- `682ebd5` (`development`): read-only role summaries, eight student record pages, `DashboardModel`, notification list, password toggles/confirmation matching, safe first-administrator provisioning, and focused tests.

The checkpoint commit `d75743d` records deletion of the Phase 3 administrator CRUD files. Checkpoint `3b437ef` records deletion of the dashboard read model, student section, notification component, and provisioning files. Because these are parallel branches/checkpoints rather than ancestors of the active branch, features must be integrated selectively; switching branches or restoring whole snapshots would overwrite the current theme and Phase 2 work.

No RIMS screenshot asset is present in the project tree. The visual refactor therefore follows the supplied textual design direction and color palette without copying external branding.

## UI conflict diagnosis

| Affected file(s) | Observed conflict or root cause | Recommended correction |
|---|---|---|
| `app/views/home.php`, `public/index.php` | The public view owns its complete HTML document and is required directly, unlike the route-selected auth/app layouts. It has no footer. This creates a separate layout path, though it does not currently render a duplicate header. | Add one public layout; make the home view a content partial; route it through the same layout dispatcher. |
| `app/views/layouts/auth.php`, `app/views/layouts/app.php`, `app/views/home.php` | Bootstrap and local asset imports are repeated in source across three layouts. Each response currently loads only one layout, so there is no duplicate Bootstrap import in an individual response. | Centralize shared head/assets markup and keep one import per response. Load Bootstrap JavaScript only where interactive Bootstrap widgets are used. |
| `public/assets/css/app.css`, `public/assets/css/components.css` | `.form-control:focus` and `.form-select:focus` are defined twice. `.brand-mark` is also defined in both files, and `app.css` retains unused Phase 0 card/status styles. The `--dark-navy` token is `#142B4A`, not the current requested dark blue `#203D78`. | Keep palette and global focus behavior in `app.css`; keep component geometry in `components.css`; remove unused Phase 0 selectors and use the canonical dark-blue token. |
| `public/assets/css/components.css`, `public/assets/css/responsive.css` | The component sheet is 1,200 lines and includes home/auth/app responsive rules; the responsive sheet contains app-shell/sidebar behavior. This is not a second stylesheet system, but responsibilities are mixed and future overrides are hard to trace. | Keep component rules and their page-specific breakpoints together; keep only cross-layout shell behavior in `responsive.css`; avoid duplicate selectors. |
| `app/views/layouts/app.php`, `app/views/components/sidebar.php`, `app/views/components/topbar.php` | The shared app layout always calls the workspace a Phase 2 placeholder. The sidebar renders many feature labels as inert spans, and the notification bell is a button with no action. | Use the shared app shell for implemented role routes, render links only for available routes, show honest read-only notices, and remove or connect nonfunctional controls. |
| `app/views/components/sidebar.php`, `app/views/dashboards/*.php`, `routes/web.php` | The current branch only routes role dashboards; student menu entries are placeholders and dashboard views explicitly defer all functionality. The historical role pages use the same shared layout. | Restore only history-backed routes and read-only views. Keep adviser assignments, attendance writes, evaluations, and other schema-unsupported workflows unavailable. |
| `app/views/auth/login.php`, `app/views/auth/register.php`, `public/assets/js/app.js` | Password toggle markup/handlers and registration confirmation matching present in `682ebd5` were removed in checkpoint `3b437ef`. Current `.password-toggle` CSS remains but is disconnected. | Restore the reviewed controls and their vanilla-JS behavior; keep server-side validation authoritative. |
| `app/views/auth/register.php`, `app/controllers/AuthController.php` | The registration select currently offers years 1–5, while server validation and the schema accept years 1–8. The 6th–8th-year values became unreachable from the UI. | Restore the supported 6–8 options without changing validation or schema. |
| `app/helpers/view.php`, `app/views/components/notification-list.php` | The notification component was removed, while notification data and notification-list CSS remain in the historical dashboard implementation. | Restore the component through the existing allowlisted component renderer and display only account-scoped notifications. |
| `README.md` | The file is absent in the active tree; history shows it was added with Phase 3 and later deleted. | Recreate concise, accurate setup, route, feature-status, and test documentation. |

### Layout/import findings

Each current route selects one of `home`, `auth`, or `app`; the auth and app layouts each render one document and one Bootstrap CSS import. The app layout includes Bootstrap JavaScript once for its profile dropdown. The sidebar script is included only by the app layout and initializes through one IIFE. The duplicate risk is repeated layout markup in source, not multiple imports or headers in a single current response.

## Functionality inventory

The “Current status” column records the baseline at the start of this audit, before the restoration below.

| Functionality | Previous implementation | Current status on `ProgressDevelopment` | Repair |
|---|---|---|---|
| Student registration, login, logout, CSRF, secure sessions, password hashing | `AuthController`, `User`, `Program`, auth helpers/middleware, `tests/authentication.php` | Present; current field names and POST actions match the controller. Existing tests pass. | Preserve unchanged; restore only removed UI behavior and supported year options. |
| Password visibility and confirmation matching | `app.js`, auth views in `682ebd5` | Removed/disconnected; CSS remains. | Restore toggle controls and confirmation feedback. |
| Admin dashboard and account management | `AdminController`, `AdminModel`, `admin/*` views, `tests/admin.php` in `aff846a` | Entire feature set absent; admin route is only a placeholder. | Integrate reviewed create/view/update, search/filter, pagination, status, and relationship-aware operations. Do not add hard deletes. |
| Program management / BSCS protection | Admin model/controller and tests in `aff846a` | Missing from active routes and views. | Restore while preserving the official BSCS record and its protection. |
| Company, supervisor association, position, skills, requirement-type management | Admin model/controller and views in `aff846a` | Missing from active routes and views. | Restore tested create/update and association operations; preserve foreign-key and capacity restrictions; use status/deactivation instead of deletion. |
| Role-specific dashboard summaries | `DashboardModel`, dashboard views, `tests/dashboard_read_model.php` in `682ebd5` | All dashboards are placeholders; the model and tests are absent. | Restore actual, read-only summaries; keep all data scoped by role and account. |
| Student profile, skills, preferences, placement, requirements, attendance, progress, notifications pages | `DashboardModel::studentPage()`, `student/section.php`, eight routes in `682ebd5` | Routes and shared view are absent. | Restore account-scoped read-only pages with empty/error states. Do not add writes. |
| Supervisor trainee visibility | `DashboardModel::supervisorSummary()` in `682ebd5` | Missing. | Restore only placements at companies linked through `company_supervisors`. |
| Adviser student monitoring | Historical read model explicitly sets `assignment_relationship_available` to false; no adviser-assignment table exists in migration `001`. | Not implemented, and no schema relationship supports it. | Keep the honest empty state; do not expose all students or invent assignments. |
| Notifications | Historical notification query/component and dashboard tests in `682ebd5` | Component/model/routes were removed; a decorative bell remains. | Restore account-scoped read-only lists; remove the nonfunctional bell control. |
| First administrator provisioning | `AdministratorProvisioner`, CLI scripts, and `tests/administrator_provisioning.php` in `682ebd5` | Removed. No default administrator is created. | Restore a CLI-only, explicit first-admin tool only after reviewing its safeguards. Do not run it or create an account. |
| Delete operations | No delete action exists in the reviewed historical admin controller; foreign keys restrict deletion of referenced records. | Not present. | Do not invent destructive deletes; retain safe status changes and relationship checks. |
| CRUD, reports, matching, attendance/evaluation writes, notifications delivery | No matching implementation or route in the reviewed branches/schema. | Not implemented. | Document as future work; do not represent as restored functionality. |

## Corrections performed

1. Integrated the reviewed administrator operations and dashboard/student read models from preserved history without switching branches or changing completed migrations.
2. Established one public/auth/app layout path with a shared asset head, public header/footer, route-aware sidebar, and shared notification component.
3. Removed duplicate focus/brand styling and unused Phase 0 rules, corrected the dark-blue token to `#203D78`, and retained semantic success/warning/error colors.
4. Restored password visibility, password-confirmation feedback, staff account password toggles, form confirmation prompts, and registration year levels 6–8.
5. Restored admin and student routes/views with role checks, CSRF, self/company scoping, safe status/relationship rules, and no new delete behavior.
6. Added route/component regression checks and reran authentication, admin, dashboard, provisioning, PHP/JavaScript syntax, and MySQL rollback/read-only checks.

The status-badge implementation had a regression: substring matching classified `inactive` as `active`. It now uses explicit normalized status values; `tests/dashboard_read_model.php` covers the inactive case.

### Verification limitations

HTTP checks confirmed public/auth responses, local assets, and guest redirection. The integrated browser page share was declined, so no rendered screenshot, computed-style, responsive viewport, or browser-console verification is claimed. No authenticated admin session was available for browser CRUD testing. The MySQL rollback check verified BSCS and left no test account; no migration or persistent database change occurred.
