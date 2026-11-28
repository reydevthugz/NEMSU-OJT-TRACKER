# Database

The schema is defined for MySQL 8.4 using InnoDB and `utf8mb4_unicode_ci`.

## Migration

Run migrations only from a real local development environment:

```powershell
php database/migrate.php
```

The command:

- creates a migration-tracking table if needed;
- applies each `database/migrations/*.sql` file once;
- skips already applied migrations;
- makes no automatic destructive database changes.

Configure the root `.env` with the local database host, port, database name, and username before migration. `DB_PASSWORD` may intentionally be empty for a local MySQL account; placeholder values are rejected. The migration command never prints credentials.

## Academic program seed

The official BSCS program can be added idempotently with:

```powershell
php database/seed_programs.php
```

The seeder verifies the `programs` columns and unique indexes, uses prepared statements, and never modifies a matching existing record. It stops rather than overwriting a conflicting record.

## Screenshot data seed

For local documentation screenshots, seed one account for each role and realistic linked records:

```powershell
php database\seed_screenshot_data.php
```

The seeder uses temporary NEMSU-style accounts and the password `TempPass!2026`. It is idempotent and should only be run in a local/test database. Full account and screenshot guidance is in `docs/SCREENSHOT_TEST_ACCOUNTS.md`.

The actual MySQL registration-to-program association can be checked without retaining a test account:

```powershell
php tests/mysql_registration_rollback.php
```

The check performs the registration inside an outer transaction, verifies the student-profile/program join, and rolls the transaction back before reporting success.

## Core tables

`users`, `programs`, `student_profiles`, `companies`, `company_supervisors`, `company_positions`, `skills`, `student_skills`, `position_skills`, `student_preferences`, `placements`, `requirement_types`, `requirement_submissions`, `attendance_records`, `evaluations`, and `notifications`.

Historical placement, requirement, attendance, and evaluation records use `RESTRICT` deletion behavior where another record is retained. Student skill and preference records may be removed with their parent profile. Notification records may be removed with their user.

## Business rules outside constraints

- Assignment and coordinator approval are separate application workflow states.
- User role and account status must be validated by application policy.
- A single student cannot have duplicate priority preferences.
- Placement occupancy and available slots require application-level coordination.
- Attendance time and total-hours consistency requires application validation.
- Submission file storage and size/type policy require application validation.
- Supervisor authorization for evaluations and attendance verification requires application-role and placement checks.
