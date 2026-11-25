<?php
require dirname(__DIR__) . '/app/bootstrap.php';

$username = environment_value('DB_USERNAME');
$password = environment_value('DB_PASSWORD');
$database = environment_value('DB_DATABASE');

$checks = [
    'username_is_null' => $username === null,
    'username_is_empty' => $username === '',
    'username_is_placeholder' => $username === 'your_mysql_username',
    'password_is_null' => $password === null,
    'password_is_placeholder' => $password === 'your_mysql_password',
    'database_matches' => $database === 'nemsu_ojt_tracker',
];

foreach ($checks as $name => $result) {
    echo $name . ': ' . ($result ? 'TRUE' : 'FALSE') . PHP_EOL;
}
