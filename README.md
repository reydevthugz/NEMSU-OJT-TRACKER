# NEMSU OJT TRACKER

On-the-Job Training Placement and Monitoring System built with native PHP, Bootstrap 5, vanilla JavaScript, and PDO/MySQL.

## Requirements

- PHP 8.3 with PDO MySQL
- MySQL 8.4
- Apache (Laragon rewrite configuration)

## Local setup

1. Copy `.env.example` to `.env` if the local environment file does not exist.
2. Set `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` for your local MySQL account. Never place `.env` under `public/` or commit it.
3. Use the existing schema. Do not rerun `database/migrate.php` on a database where the migration has already been applied.
4. Open `http://localhost/nemsu-ojt-tracker/`.
5. Students can register through the public registration page. Administrator access requires an explicitly provisioned administrator; the application does not create a default account.

If the database owner authorizes initial administrator provisioning, run the CLI-only PowerShell prompt from the project root:

```powershell
.\database\provision-admin.ps1
```

The utility refuses to create a second administrator or reuse an existing email. It is not invoked by the web application and was not run as part of this audit.

## Implemented features

- Student registration, login/logout, secure sessions, password hashing, CSRF protection, and role-based access checks.
- Administrator management of accounts and academic/company reference data, including supervisor-company associations and position capacity rules.
- Read-only student profile/skills/preferences/placement/requirements/attendance/progress/notification pages.
- Database-backed role summaries; supervisor data is limited to companies linked to that account.
- Public, authentication, and authenticated application layouts with responsive Royal Blue/Orange theming.

Master records are not hard-deleted. The current schema has no adviser-to-student assignment relationship, and placement matching/assignment, approvals, attendance updates/hour calculations, evaluations, notification delivery, and reports are not implemented.

## Tests

Run isolated tests from the project root:

```powershell
php tests\authentication.php
php tests\admin.php
php tests\dashboard_read_model.php
php tests\administrator_provisioning.php
php tests\ui_routes.php
```

`tests\mysql_readonly_probe.php` inspects the configured database without writing. `tests\mysql_registration_rollback.php` verifies the BSCS association inside a transaction and rolls back its temporary account.

## Architecture

`public/index.php` dispatches `routes/web.php`. Controllers, models, middleware, helpers, layouts, and views live under `app/`. Database configuration is read from the project-root `.env`; migrations and explicit CLI utilities live under `database/`. No framework or Composer dependencies are required.
