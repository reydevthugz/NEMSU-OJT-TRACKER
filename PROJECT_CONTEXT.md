# NEMSU OJT TRACKER

## Project overview

NEMSU OJT TRACKER is the On-the-Job Training Placement and Monitoring System for supporting the placement and monitoring of students during their on-the-job training.

## Objectives

- Provide a system for coordinating OJT placement and monitoring activities.
- Support the intended OJT participants and coordinators through a centralized application.
- Establish a secure, maintainable foundation before implementing operational modules.

These objectives describe the intended system; Phase 0 does not implement placement or monitoring functionality.

## Target users

The user roles are students, advisers, coordinators, company supervisors, and administrators. Authentication and role checks were added in Phase 2; they were not part of Phase 0.

## Planned system modules

The project requirements identify future modules for student and company management, skills, placement matching and assignment, coordinator approval, document submissions, attendance and OJT hours, progress monitoring, supervisor evaluation, notifications, dashboards, and reports. None of these modules is included in Phase 0.

Administrator authentication, dashboard summaries, user account management, and master-data management for programs, companies, supervisors, positions, skills, and requirement types were implemented in Phase 3. Placement workflows and student submissions remain future work.

## Technology stack

- Backend: Native PHP 8.3; no PHP framework.
- Frontend: Bootstrap 5, custom CSS, and vanilla JavaScript.
- Database: MySQL 8.4 with PDO and `utf8mb4`.
- Local development: Laragon with Apache on Windows.

## Architecture

The application uses a lightweight MVC-inspired structure: controllers, models, views, middleware, and helpers are under `app/`. Configuration, database migrations, the public web root, and routes remain at the project root in `config/`, `database/`, `public/`, and `routes/`; `storage/` is also at the project root. `public/index.php` is the web entry point. The project-root Apache `.htaccess` denies direct requests to application/configuration directories and internally routes requests to `public/`.

## Database decisions

- Database name: `nemsu_ojt_tracker`.
- Connections are configured through PDO using environment variables.
- The connection uses the MySQL `utf8mb4` character set, exception error mode, associative fetches, and native prepared statements.
- Credentials belong in the root `.env` file and must not be committed.
- Phase 0 creates no database tables and does not require a live connection.

## Important business rules

Detailed business rules have not yet been specified. OJT workflows, eligibility, placement, approval, attendance, hour calculations, and evaluation rules must be defined and approved before their future implementation. Phase 0 enforces no business rules.

## Development restrictions

- Use native PHP; do not introduce a PHP framework.
- Keep the frontend to Bootstrap 5, custom CSS, and vanilla JavaScript.
- Do not commit credentials or expose private configuration under `public/`.
- Do not create operational database tables or implement future-phase modules during Phase 0.
- Preserve existing project history and files when developing subsequent phases.

## Development phases

- Phase 0: Project initialization and environment foundation.
- Phase 1: UI/UX structure, reusable PHP view components, authentication page designs, role dashboard prototypes, and responsive layouts.
- Phase 2: Relational database design, native migrations, secure student registration/login/logout, sessions, CSRF, and reusable server-side role middleware.
- Phase 3: Administrator access, dashboard statistics, account administration, and schema-backed master-data management.
- Subsequent phases: Placement matching and assignment, requirements submissions, attendance, OJT-hour calculations, monitoring, evaluation, notifications, and reporting remain unimplemented.

## Current implementation status

Phase 2 schema migration is complete, and student registration, login, logout, session security, CSRF protection, and role-protected dashboards are implemented. The official Bachelor of Science in Computer Science program (BSCS) is configured for registration and protected from administrative edits. Phase 3 administrator pages cover account management and supported master data. No placement assignment, document submission, attendance, evaluation, or other operational OJT workflow is active.

The Phase 2 migration is complete and must not be rerun or modified as part of Phase 3. No default administrator or sample operational records are created. The current configured database contains no users, so an administrator must be provisioned by the project owner before the administrator pages can be exercised interactively.
