<?php

declare(strict_types=1);

require_once __DIR__ . '/view.php';

function start_secure_session(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_trans_sid', '0');

        $httpsValue = strtolower((string) ($_SERVER['HTTPS'] ?? ''));
        $secure = $httpsValue !== '' && !in_array($httpsValue, ['off', '0'], true);
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_cache_limiter('nocache');
        session_start();
    }

    $now = time();
    if (isset($_SESSION['auth']['user_id'])) {
        $lastActivity = (int) ($_SESSION['auth']['last_activity'] ?? $_SESSION['auth']['login_at'] ?? $now);
        if ($now - $lastActivity > 1800) {
            destroy_secure_session();
            session_start();
        } elseif ($lastActivity > 0) {
            $_SESSION['auth']['last_activity'] = $now;
        }
    }

    regenerate_csrf_token();
}

function regenerate_csrf_token(): void
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

function csrf_token(): string
{
    start_secure_session();

    return (string) $_SESSION['csrf_token'];
}

function verify_csrf_post(): bool
{
    $submitted = $_POST['csrf_token'] ?? '';
    $stored = $_SESSION['csrf_token'] ?? '';
    if (!is_string($submitted) || !is_string($stored)) {
        return false;
    }

    return $submitted !== '' && $stored !== '' && hash_equals($stored, $submitted);
}

function set_flash(string $type, mixed $value): void
{
    $_SESSION['flash'][$type] = $value;
}

function take_flash(string $type): mixed
{
    $value = $_SESSION['flash'][$type] ?? null;
    unset($_SESSION['flash'][$type]);

    return $value;
}

function is_authenticated(): bool
{
    return isset($_SESSION['auth']['user_id'], $_SESSION['auth']['role']);
}

function authenticated_role(): ?string
{
    return is_authenticated() ? (string) $_SESSION['auth']['role'] : null;
}

function authenticated_user(): ?array
{
    return is_authenticated() ? $_SESSION['auth'] : null;
}

function role_is_authorized(string $role, array $allowedRoles): bool
{
    return $role === 'admin' || in_array($role, $allowedRoles, true);
}

function establish_authenticated_session(array $user): void
{
    $role = $user['role'] ?? null;
    if (
        !isset($user['id'], $user['name'], $user['email'])
        || !is_string($role)
        || !in_array($role, ['student', 'adviser', 'coordinator', 'supervisor', 'admin'], true)
    ) {
        throw new InvalidArgumentException('Cannot establish a session for an invalid account.');
    }

    start_secure_session();
    if (!session_regenerate_id(true)) {
        throw new RuntimeException('Session ID regeneration failed.');
    }

    $now = time();
    $_SESSION['auth'] = [
        'user_id' => (int) $user['id'],
        'name' => (string) $user['name'],
        'email' => (string) $user['email'],
        'role' => $role,
        'login_at' => $now,
        'last_activity' => $now,
    ];

    unset($_SESSION['csrf_token']);
    regenerate_csrf_token();
}

function destroy_secure_session(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $params['path'],
            'domain' => $params['domain'],
            'secure' => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => $params['samesite'] ?? 'Lax',
        ]);
    }

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
        session_id('');
    }
}

function app_base_path(): string
{
    $scriptDirectory = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));

    return preg_replace('~/public$~', '', rtrim($scriptDirectory, '/')) ?: '';
}

function redirect_to_page(string $page): never
{
    $base = app_base_path();
    $location = $base . '/?page=' . rawurlencode($page);
    header('Location: ' . $location, true, 302);
    exit;
}

function redirect_to_dashboard(string $role): never
{
    redirect_to_page(dashboard_page_for_role($role));
}

function dashboard_page_for_role(string $role): string
{
    return match ($role) {
        'student' => 'dashboard/student',
        'adviser' => 'dashboard/adviser',
        'coordinator' => 'dashboard/coordinator',
        'supervisor' => 'dashboard/supervisor',
        'admin' => 'dashboard/admin',
        default => 'dashboard/student',
    };
}
