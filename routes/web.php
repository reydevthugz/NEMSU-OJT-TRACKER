<?php

declare(strict_types=1);

return [
    'home' => [
        'view' => 'home',
        'layout' => 'home',
        'title' => 'NEMSU OJT TRACKER',
        'subtitle' => 'On-the-Job Training Placement and Monitoring System',
    ],
    'login' => [
        'view' => 'auth/login',
        'layout' => 'auth',
        'title' => 'Welcome back',
        'subtitle' => 'Sign in to your account',
        'guest_only' => true,
    ],
    'register' => [
        'view' => 'auth/register',
        'layout' => 'auth',
        'title' => 'Student registration',
        'subtitle' => 'Create your student account',
        'guest_only' => true,
    ],
    'dashboard/student' => [
        'view' => 'dashboards/student',
        'layout' => 'app',
        'role' => 'Student',
        'title' => 'Student dashboard',
        'subtitle' => 'Student workspace placeholder.',
        'allowed_roles' => ['student'],
    ],
    'dashboard/adviser' => [
        'view' => 'dashboards/adviser',
        'layout' => 'app',
        'role' => 'Adviser',
        'title' => 'Adviser dashboard',
        'subtitle' => 'Adviser workspace placeholder.',
        'allowed_roles' => ['adviser'],
    ],
    'dashboard/coordinator' => [
        'view' => 'dashboards/coordinator',
        'layout' => 'app',
        'role' => 'Coordinator',
        'title' => 'Coordinator dashboard',
        'subtitle' => 'Coordinator workspace placeholder.',
        'allowed_roles' => ['coordinator'],
    ],
    'dashboard/supervisor' => [
        'view' => 'dashboards/supervisor',
        'layout' => 'app',
        'role' => 'Company Supervisor',
        'title' => 'Supervisor dashboard',
        'subtitle' => 'Supervisor workspace placeholder.',
        'allowed_roles' => ['supervisor'],
    ],
    'dashboard/admin' => [
        'view' => 'dashboards/admin',
        'layout' => 'app',
        'role' => 'System Administrator',
        'title' => 'Administrator dashboard',
        'subtitle' => 'Administrator workspace placeholder.',
        'allowed_roles' => ['admin'],
    ],
    'error/403' => [
        'view' => 'errors/403',
        'layout' => 'auth',
        'title' => 'Access restricted',
        'subtitle' => 'Error page preview',
    ],
    'error/404' => [
        'view' => 'errors/404',
        'layout' => 'auth',
        'title' => 'Page not found',
        'subtitle' => 'Error page preview',
    ],
    'error/500' => [
        'view' => 'errors/500',
        'layout' => 'auth',
        'title' => 'Something went wrong',
        'subtitle' => 'Error page preview',
    ],
];
