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

## System modules and current scope

Implemented foundation features include student registration and sign-in, role-protected workspaces, administrator account/reference-data management, read-only student record pages, role-specific database-backed summaries, and account-scoped notifications.

The broader product may later include placement matching/assignment, coordinator approval, document submissions, attendance verification, OJT-hour calculations, progress actions, supervisor evaluations, notification delivery, and reports. Those operational workflows are not implemented by the current build.

## Technology stack

- Backend: Native PHP 8.3; no PHP framework.
- Frontend: Bootstrap 5, custom CSS, and vanilla JavaScript.
- Database: MySQL 8.4 with PDO and `utf8mb4`.
- Local development: Laragon with Apache on Windows.

## Architecture

The application uses a lightweight MVC-inspired structure: controllers, models, views, middleware, and helpers are under `app/`. Public, authentication, and authenticated application pages use separate canonical layouts with shared asset markup and reusable components. Configuration, database migrations, the public web root, and routes remain at the project root in `config/`, `database/`, `public/`, and `routes/`; `storage/` is also at the project root. `public/index.php` is the web entry point. The project-root Apache `.htaccess` denies direct requests to application/configuration directories and internally routes requests to `public/`.

## Database decisions

- Database name: `nemsu_ojt_tracker`.
- Connections are configured through PDO using environment variables.
- The connection uses the MySQL `utf8mb4` character set, exception error mode, associative fetches, and native prepared statements.
- Credentials belong in the root `.env` file and must not be committed.
- Phase 0 creates no database tables and does not require a live connection.

## Important business rules

- Public registration creates student accounts only; passwords are hashed and role access is enforced server-side.
- Student record views are scoped to the signed-in student's profile; supervisor placement summaries are scoped to linked companies.
- The current schema has no adviser-to-student assignment relationship, so the adviser dashboard does not expose student records.
- Administrator management preserves the official BSCS program, prevents unsafe self-demotion/deactivation, respects company/placement relationships and capacity rules, and uses status changes rather than deleting master records.
- Placement matching/assignment, approval, attendance verification, OJT-hour calculations, and evaluations remain unavailable until their business rules are approved and implemented.

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
- Phase 3: Administrator account/reference-data management and read-only role/student records.
- Phase 3.5: Global UI theme and unified public, authentication, and application layouts.
- Subsequent phases: To be defined and approved by the project owner. Full placement, requirement-submission, attendance, evaluation, notification-delivery, and reporting workflows remain unimplemented.

## Current implementation status

The Phase 2 schema migration is complete. Student registration, login/logout, session security, CSRF protection, and role middleware remain in place. Phase 3 administrator management and read-only, role-scoped dashboard/student views are available. The current MySQL verification confirmed 17 tables and one configured program; the rollback test verified that program is the official BSCS record. The global UI uses Royal Blue `#4169E1`, Orange `#F28C28`, Indigo Gray `#64748B`, white surfaces, and dark blue `#203D78`. The public home page presents the system introduction, available foundation features, intended users, and account links without exposing protected dashboards. Operational placement workflows remain inactive.

The official BSCS program is protected from administrative edits. Initial administrator provisioning is an explicit CLI operation, and the application does not create a default account.
