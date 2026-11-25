# NEMSU OJT TRACKER

On-the-Job Training Placement and Monitoring System built with native PHP, Bootstrap 5, vanilla JavaScript, and PDO/MySQL.

## Requirements

- PHP 8.3 with PDO MySQL
- MySQL 8.4
- Apache with URL rewriting enabled (Laragon configuration)

## Local setup

1. Copy `.env.example` to `.env` if the local environment file does not exist.
2. Set `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` to the values configured for your own local MySQL instance. An empty password is valid when that is how the local database account is configured.
3. Use the already-installed Phase 2 schema. Do not rerun `database/migrate.php` on a database where the migration has already been applied.
4. Open `http://localhost/nemsu-ojt-tracker/` in Laragon.
5. Sign in with an administrator account provisioned by the database owner to open the protected administrator area. The project does not create a default administrator or default password.

Never commit `.env` or copy it under `public/`.

The configured database currently has no user records, so there is no administrator account available for interactive sign-in yet. Provision the initial administrator through the project owner's trusted account-provisioning procedure; do not enable public administrator registration.

## Phase 3 administrator features

Administrator pages are available after admin sign-in:

- `?page=dashboard/admin` — database-backed summary counts.
- `?page=admin/users` — account search/filtering, student profile inspection, role/status updates, and staff account creation.
- `?page=admin/programs` — program search and create/edit; the existing BSCS program is protected.
- `?page=admin/companies` — company search/filtering and create/edit/status.
- `?page=admin/supervisors` — supervisor-company association management.
- `?page=admin/positions` — position management, capacity validation, and placement counts.
- `?page=admin/skills` — skills catalog search and create/edit.
- `?page=admin/requirements` — requirement type search, create/edit, and active status.

All management pages require the administrator role; state-changing forms use POST and CSRF tokens. Master records are not deleted. Fields for activation or mandatory status are only exposed where the existing Phase 2 schema supports them.

## Tests

Run the isolated tests from the project root:

```powershell
php tests\authentication.php
php tests\admin.php
```

Both use in-memory SQLite and do not write to the configured MySQL database. The MySQL dashboard check is read-only and can be run with:

```powershell
php -r "require 'app/bootstrap.php'; require 'app/models/AdminModel.php'; echo json_encode((new AdminModel(database_connection()))->dashboardStats(), JSON_THROW_ON_ERROR), PHP_EOL;"
```

Lint PHP files with `php -l`; no package installation or database migration is needed for Phase 3.

## Scope and next phase

Phase 3 covers administrator access and master-data management only. Placement assignment/matching, student requirement submissions, attendance, OJT-hour calculations, monitoring, evaluations, notifications, and reporting are not implemented.
