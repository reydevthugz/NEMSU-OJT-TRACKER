<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__) . '/app/bootstrap.php';
require_once dirname(__DIR__) . '/app/models/Program.php';
require_once dirname(__DIR__) . '/app/models/User.php';

try {
    $db = database_connection();
    $programStatement = $db->prepare('SELECT id, code, name FROM programs WHERE code = :code LIMIT 1');
    $programStatement->execute(['code' => 'BSCS']);
    $program = $programStatement->fetch();
    if ($program === false || $program['name'] !== 'Bachelor of Science in Computer Science') {
        throw new RuntimeException('The official program is not available.');
    }

    $suffix = bin2hex(random_bytes(8));
    $email = 'phase2-check-' . $suffix . '@example.invalid';
    $studentNumber = 'PHASE2-' . strtoupper($suffix);
    $db->beginTransaction();

    try {
        $userId = (new User($db))->createStudent([
            'name' => 'Temporary Registration Verification',
            'email' => $email,
            'student_number' => $studentNumber,
            'program_id' => (int) $program['id'],
            'year_level' => 1,
            'preferred_location' => null,
            'password' => bin2hex(random_bytes(16)),
        ]);

        if (!$db->inTransaction()) {
            throw new RuntimeException('The caller transaction was unexpectedly committed.');
        }

        $associationStatement = $db->prepare(
            'SELECT p.code, p.name
             FROM student_profiles AS sp
             INNER JOIN programs AS p ON p.id = sp.program_id
             WHERE sp.user_id = :user_id'
        );
        $associationStatement->execute(['user_id' => $userId]);
        $association = $associationStatement->fetch();
        if (
            $association === false
            || $association['code'] !== 'BSCS'
            || $association['name'] !== 'Bachelor of Science in Computer Science'
        ) {
            throw new RuntimeException('The student profile was not associated with BSCS.');
        }

        $db->rollBack();
    } catch (Throwable $exception) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        throw $exception;
    }

    $cleanupStatement = $db->prepare(
        'SELECT
            (SELECT COUNT(*) FROM users WHERE email = :email)
            + (SELECT COUNT(*) FROM student_profiles WHERE student_number = :student_number)'
    );
    $cleanupStatement->execute(['email' => $email, 'student_number' => $studentNumber]);
    if ((int) $cleanupStatement->fetchColumn() !== 0) {
        throw new RuntimeException('The verification transaction left persistent test records.');
    }

    fwrite(STDOUT, "BSCS student-profile association verified; test transaction rolled back; no test account retained.\n");
} catch (Throwable $exception) {
    error_log('MySQL student registration association verification failed.');
    fwrite(STDERR, 'Association verification failed safely (' . get_class($exception) . ").\n");
    exit(1);
}
