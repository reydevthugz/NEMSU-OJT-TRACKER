# Development Log

## Phase 0 — Project initialization and environment setup

### Environment

- Operating system, Laragon, Apache, PHP 8.3.30, MySQL 8.4.3, PDO, and database name were supplied as verified project-environment details by the project owner.
- PHP CLI syntax validation was run locally with PHP 8.3.30.
- Apache HTTP serving was tested as documented below. A MySQL connection was not attempted because actual credentials are unavailable.

### Project structure

The MVC directories are organized under `app/controllers/`, `app/models/`, `app/views/`, `app/middleware/`, and `app/helpers/`. Configuration, database migrations, public assets, routes, and storage remain at the project root in their requested directories. The former root-level MVC folders were empty except for the environment helper, which was moved to `app/helpers/env.php`.

### Files created

- `.env` and `.env.example`
- `.gitignore` and project-root `.htaccess`
- `PROJECT_CONTEXT.md` and `DEVELOPMENT_LOG.md`
- `app/bootstrap.php` and `app/helpers/env.php` (the helper was moved from the root `helpers/` folder)
- `config/database.php`
- `public/index.php`
- `public/assets/css/app.css`
- `public/assets/js/app.js`
- `.gitkeep` placeholders in otherwise-empty required MVC, migration, asset-image, and route directories
- `storage/uploads/.gitkeep` and `storage/logs/.gitkeep`

### Configuration

The native environment loader reads root `.env` values without replacing variables already set by the process. PDO configuration reads `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD`; it uses `utf8mb4`, exception mode, associative fetch mode, and disabled emulated prepares. Connection failures are reported without displaying credentials.

The `.env` file contains placeholders, not actual database credentials. A temporary CLI-only PDO connection test was run through the existing bootstrap and database configuration; it failed to establish or verify the connection and printed only a sanitized error. No credentials were changed and no database tables were created. The temporary test file was removed after the run.

### Homepage

The Phase 0 landing page is implemented at `public/index.php` with Bootstrap 5, project CSS, and local vanilla JavaScript. CLI rendering and HTTP serving, including local asset responses, were verified during the validation pass below.

The existing Laragon virtual-host configuration was inspected without modification: its document root is the project root and `AllowOverride All` is enabled. The main local URL, `http://localhost/nemsu-ojt-tracker/`, returned HTTP 200 with the expected homepage. The direct `public/index.php` URL and both local assets also returned HTTP 200. A request using the virtual-host Host header returned HTTP 200. The configured `nemsu-ojt-tracker.test` hostname did not resolve during validation, so use the localhost URL unless local hostname resolution is set up.

### Git and security

Git was not initialized in the workspace. A new repository was initialized without adding or committing files. `.gitignore` excludes `.env`, `/vendor/`, and uploaded/log files; `git check-ignore` confirmed `.env` is ignored while `.env.example` is not. The root `.htaccess` returned HTTP 403 for direct requests to `.env`, `app/helpers/env.php`, `config/database.php`, `storage/logs/.gitkeep`, and `public/.env`. A check also confirmed there is no `.env`, `config/`, or `storage/` path inside `public/`. An untracked `.vscode/` directory appeared in Git status and was left untouched.

### Validation and unfinished tasks

- PHP syntax validation: passed for all four PHP files (`app/bootstrap.php`, `app/helpers/env.php`, `config/database.php`, and `public/index.php`).
- Required architecture checks: passed; all five MVC directories are under `app/`, required root directories exist, and empty legacy root MVC directories are absent. Git placeholders preserve the otherwise-empty required directories.
- Environment loader: passed for all five database keys; a separate check confirmed existing process environment values are not overwritten.
- PDO configuration: `pdo_mysql` is available, `database_connection()` is loaded, and the source config sets the requested MySQL `utf8mb4`, exception, associative-fetch, and native-prepared-statement options.
- Homepage: CLI-render and HTTP checks passed with the required system title and Phase 0 message. The homepage, direct `public/index.php` entry, and local CSS/JavaScript all returned HTTP 200.
- Apache configuration: the existing Laragon virtual host and rewrite behavior were verified without changes.
- `.env` exclusion and sensitive-path HTTP/public-directory checks: passed as described above.
- Database connection: the temporary CLI test failed to establish or verify the connection. The failure was reported without credentials or raw PDO details. Both database username and password remain placeholders; no tables were created.
- The `.test` hostname did not resolve locally; no global or Laragon Apache configuration was changed.
- No database tables or Phase 1 features were created.

## Phase 1 — UI/UX design and project layout

- Added reusable application and authentication layouts, sidebar, topbar, footer, page-header, cards, badges, tables, and empty states.
- Added login and registration visual prototypes and five static role dashboards.
- Added an allowlisted preview router and responsive CSS/JavaScript.
- Browser validation at desktop, tablet, and mobile widths passed without console errors or horizontal overflow.

## Phase 2 — Database design and authentication

- Added the core Phase 2 schema migration and CLI migration runner.
- Added users, programs, student profiles, companies, supervisors, positions, skills, preferences, placements, requirements, attendance, evaluations, notifications, indexes, foreign keys, and numeric checks.
- Added student registration, login, logout, password hashing and verification, prepared statements, transactions, CSRF tokens, secure session settings, 30-minute inactivity timeout, and session-ID regeneration.
- Added reusable authentication and role middleware for student, adviser, coordinator, supervisor, and administrator dashboards.
- Preserved the existing Phase 1 layouts. Login and registration forms now submit actual POST actions.
- `.env` database credentials remain placeholders, so migration execution, live connection testing, and database-backed authentication tests could not be performed.
- No database tables or data were created and no migration was run.

### Actual Phase 2 validation

- PHP syntax passed for 33 PHP files.
- Migration guard executed and stopped safely because `.env` still contains placeholder credentials. It exited before creating the migration-tracking table or schema.
- HTTP homepage and login/registration routes returned 200.
- Protected student, adviser, coordinator, supervisor, and administrator dashboard routes each redirected guests to login.
- Unknown preview routes returned 404.
- CSS, JavaScript, and favicon assets returned 200.
- Invalid-CSRF registration POST returned 302 to the registration page and displayed the safe “form session expired” message.
- Login and registration POST handlers were exercised; database-backed success, duplicate checks, and password verification could not complete because the database credentials remain placeholders.
- Browser login page loaded without the session-cache warning found during the first Phase 2 browser check.
- A session cookie was created during POST smoke tests.
- No plaintext password, fake account, migration execution, or database table creation occurred.

### Migration failure diagnostics follow-up

- Read-only verification confirmed the root `.env` values load as expected and the PDO helper can connect to `nemsu_ojt_tracker` using the configured empty password.
- The database currently reports zero tables; no migration SQL was executed during this investigation.
- Earlier PHP log entries contained only the generic `Database connection failed.` message, so their original PDO error code cannot be recovered.
- Preserved PDO connection exceptions as chained causes and changed migration failure output/logging to report a fixed execution stage plus SQLSTATE/driver code only, never raw exception text.
- PHP syntax checks passed for `config/database.php` and `database/migrate.php`; the migration file is readable and the runner's parser discovers 16 non-empty SQL statements.

### Migration credential-guard troubleshooting

- Confirmed `database/migrate.php` loads `app/bootstrap.php`; the bootstrap resolves the root `.env` and loads it through `app/helpers/env.php`.
- Cleared inherited database environment variables in a CLI-only check and confirmed all five expected keys load from the root `.env`; `DB_PASSWORD` is present and empty, the username is not the documented placeholder, and the database name matches the expected target.
- Fixed the migration preflight guard to allow an intentionally empty MySQL password while continuing to reject a missing password and placeholder values. No migration or database connection was run during this check.

### Phase 2 authentication and authorization implementation

- Connected student registration and login to the existing forms and PDO models. Registration validates scalar inputs, checks the program/email/student number, hashes the password, forces the `student` role, and creates the user/profile pair in one transaction.
- Login uses prepared statements, `password_verify()`, active-account and known-role checks, generic credential errors, and session-ID regeneration. Password registration is limited to 72 bytes to avoid bcrypt truncation with the current `PASSWORD_DEFAULT`.
- Added strict cookie-only session settings, HttpOnly/SameSite cookies, HTTPS-only Secure cookies, a 30-minute inactivity timeout, CSRF validation, and POST-only logout/session destruction.
- Role dashboards are protected server-side. Unauthorized role access now returns HTTP 403; administrator override follows the documented policy.
- Reused the Phase 1 layout and form styling. Updated the dashboards to role-specific placeholders without illustrative operational records.
- Registration now reports database/program-list failures without showing credentials or internal exception details. It also explains when no academic programs are configured; no program rows or default accounts were inserted.
- Added `tests/authentication.php`, a repeatable, dependency-free test script using only an in-memory SQLite database. It does not write to the configured MySQL database.
- Added `tests/mysql_readonly_probe.php` to verify the configured PDO/MySQL connection and inspect table/program counts using read-only queries only.
- `php tests/authentication.php` passed 44 checks using the isolated in-memory SQLite database, including role-to-dashboard mapping, session-ID/CSRF rotation, middleware denial, and inactivity expiry.
- PHP syntax validation passed for all 36 PHP files.
- The read-only MySQL probe connected to `nemsu_ojt_tracker` on MySQL 8.4.3 and confirmed 17 tables (16 application tables plus migration tracking). No program rows are configured yet.
- HTTP checks returned 200 for login and registration pages; guest requests to all five protected dashboards redirected to login. No account or database data was created during these checks.
- HTTP form checks passed for server-side registration validation, generic invalid-login feedback through a read-only account lookup, and CSRF-protected logout.
- Successful registration and login against MySQL were not exercised: there are no configured programs, and no test account was inserted. The repeatable model/auth tests ran against SQLite memory only.
- No migration was rerun and no MySQL write statements were executed during this implementation.

### Phase 2 finalization - official academic program

- Inspected the live `programs` schema: `id BIGINT UNSIGNED AUTO_INCREMENT` primary key; required `code VARCHAR(30)` and `name VARCHAR(150)`; `created_at` and auto-updated `updated_at` timestamps; unique indexes on `code` and `name`.
- Added `database/seed_programs.php`, a CLI-only, prepared-statement seeder. It validates the required schema and unique indexes, inserts only BSCS, detects an exact existing record without changing it, and stops safely on conflicting code/name records.
- First seeder run inserted `BSCS - Bachelor of Science in Computer Science` as program ID 1. A second run detected the same record and made no changes.
- Confirmed `Program::all()` feeds the registration selector and `AuthController` validates `program_id` before `User::createStudent()` writes it to `student_profiles.program_id`.
- Added a MySQL registration association check. It created a temporary user/profile within an outer transaction, verified the joined profile referenced BSCS, rolled back, and confirmed no test account remained.
- Registration page returned HTTP 200 and rendered the BSCS option. The read-only probe confirmed MySQL 8.4.3, 17 tables, and one program.
- PHP syntax passed for all 38 PHP files; `php tests/authentication.php` passed 47 checks.
- The migration file was not modified or rerun. No test account/profile data was retained.

## Phase 3 — Administrator access and master-data management

- Added allowlisted, administrator-only routes and centralized server-side authorization for every management action. The front controller checks the role before dispatching admin POST actions; the admin controller independently checks the active database role/status. Existing login, student registration, logout, and role middleware remain in place.
- Added dashboard totals using database aggregation and table counts. A read-only query against the configured MySQL database returned 0 users (0 students, advisers, coordinators, and supervisors), 1 program, 0 companies, 0 skills, 0 company positions, and 0 requirement types.
- Added account search/filter/pagination, password-hash-free account details and name/email editing, staff-account creation with password hashing, role/status management, protection against self-demotion/deactivation, and a server-side guard against assigning a user without a student profile to the student role.
- Added program management with BSCS protected; company create/view/edit/status management; supervisor-company association management; position management with active-placement capacity validation and placement-history protection; skills management; and requirement type management with the schema-supported active flag.
- No migration or database schema changes were needed. The Phase 2 migration and records were not modified. No admin, test, company, skill, requirement, or other operational data was written to MySQL.
- Added CSRF-protected POST forms, prepared statements, output escaping, server-side validation, sanitized database error feedback, and confirmation prompts for sensitive account/status/association edits. No delete operations or unsupported activation/mandatory controls were added.
- Added responsive Bootstrap forms/tables and working administrator navigation. PHP view smoke tests confirmed that management forms render CSRF fields and listing tables use responsive wrappers. A full authenticated browser/viewport check was not possible because the configured database currently has no user or administrator accounts.
- Added `README.md`, updated `PROJECT_CONTEXT.md` and this log, and added isolated in-memory SQLite administrative tests.
- Validation: `php tests\admin.php` passed 44 checks; `php tests\authentication.php` passed 49 checks; PHP syntax passed for all 51 PHP files; `git diff --check` reported no whitespace errors.
- MySQL verification was read-only: the admin dashboard query connected successfully and returned database counts; a separate SELECT confirmed the existing BSCS row remains ID 1 with code `BSCS` and name `Bachelor of Science in Computer Science`. No live administrative CRUD mutation was attempted.
- HTTP check of `/?page=admin/users` while unauthenticated returned HTTP 302 to the login page. Middleware tests confirmed non-admin roles receive HTTP 403 for admin-only access.
- Remaining setup limitation: no administrator account currently exists in the configured database. The administrator area cannot be interactively signed into until the project owner provisions an administrator through a trusted process. No default account was created.
