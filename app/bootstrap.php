<?php

declare(strict_types=1);

require_once __DIR__ . '/helpers/env.php';

load_environment(dirname(__DIR__) . '/.env');

require_once dirname(__DIR__) . '/config/database.php';

require_once __DIR__ . '/helpers/view.php';
