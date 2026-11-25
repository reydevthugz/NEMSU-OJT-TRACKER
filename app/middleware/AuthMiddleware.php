<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/helpers/auth.php';

final class AuthMiddleware
{
    public function requireAuthentication(): void
    {
        start_secure_session();
        if (!is_authenticated()) {
            set_flash('error', 'Please sign in to continue.');
            redirect_to_page('login');
        }
    }

    public function requireRole(array $allowedRoles): bool
    {
        $this->requireAuthentication();

        $role = authenticated_role();
        if ($role === null || !role_is_authorized($role, $allowedRoles)) {
            http_response_code(403);
            return false;
        }

        return true;
    }
}
