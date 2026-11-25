# Authentication and Authorization

## Registration

Student registration accepts server-side fields for full name, email, student number, program, year level, optional preferred location, password, and password confirmation. The public form cannot assign a role; the server always creates `users.role = 'student'`. Passwords must be between 8 and 72 bytes to avoid bcrypt truncation with the current `PASSWORD_DEFAULT`.

User and student-profile inserts run in one database transaction.

## Login

Login compares the submitted password with `password_hash()` output using `password_verify()`. It accepts only active accounts. Login failures use a generic credential message.

After authentication, PHP regenerates the session ID and stores only user ID, name, email, role, login time, and last-activity time in the session. Only active accounts with a recognized role can sign in; failures use a generic credential message.

## Logout

Logout requires POST and CSRF validation. It clears session data, expires the session cookie, and destroys the PHP session.

## Session security

- HttpOnly cookies
- SameSite=Lax cookies
- Secure cookies automatically enabled for HTTPS requests
- Strict session IDs, cookie-only sessions, and URL session propagation disabled
- Session ID regenerated after login
- 30-minute inactivity timeout
- No credentials or sessions placed in URLs

## Authorization

Dashboard routes declare allowed roles in `routes/web.php`. `AuthMiddleware` checks authentication and role before rendering. The server redirects guests to login and returns HTTP 403 for denied role combinations.

Current dashboard policy:

- Student: student dashboard
- Adviser: adviser dashboard
- Coordinator: coordinator dashboard
- Supervisor: supervisor dashboard
- Administrator: administrator dashboard

An administrator is accepted through the reusable role middleware as a platform-level override. Sidebar labels never replace server-side checks.

## Verification

Run the isolated, dependency-free authentication checks with:

```powershell
php tests/authentication.php
```

These checks use an in-memory SQLite database and do not write to the configured MySQL database. They cover student-role assignment, registration transaction rollback, hashed passwords, active-account login checks, prepared-statement behavior, role rules, CSRF validation, session cookie settings, idle timeout, and session destruction.

Verify the configured MySQL connection and current schema without writing data with:

```powershell
php tests/mysql_readonly_probe.php
```

The current registration selector reads the configured BSCS program from `programs`; no default staff/admin accounts are created. Register a student, sign in, visit each `?page=dashboard/...` route while signed in, and submit logout from the top-bar menu. A student attempting another role's dashboard should receive HTTP 403. Use an approved local account for manual checks.
