<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/helpers/auth.php';

final class AuthController
{
    public function register(): void
    {
        start_secure_session();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect_to_page('register');
        }

        if (!verify_csrf_post()) {
            set_flash('error', 'Your form session expired. Please try again.');
            redirect_to_page('register');
        }

        $name = trim($this->postString('name'));
        if ($name === '') {
            $name = trim(
                $this->postString('first_name') . ' '
                . $this->postString('last_name')
            );
        }
        $email = strtolower(trim($this->postString('email')));
        $studentNumber = trim($this->postString('student_number'));
        $programId = $this->postInteger('program_id');
        $yearLevel = $this->postInteger('year_level');
        $preferredLocation = trim($this->postString('preferred_location'));
        $password = $this->postString('password');
        $passwordConfirmation = $this->postString('password_confirmation');

        $errors = [];
        if ($name === '' || strlen($name) > 120) {
            $errors[] = 'Enter a valid full name.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
            $errors[] = 'Enter a valid email address.';
        }
        if ($studentNumber === '' || strlen($studentNumber) > 50) {
            $errors[] = 'Enter a valid student number.';
        }
        if ($programId === false || $programId < 1) {
            $errors[] = 'Select an academic program.';
        }
        if ($yearLevel === false || $yearLevel < 1 || $yearLevel > 8) {
            $errors[] = 'Select a valid year level.';
        }
        if (strlen($password) < 8 || strlen($password) > 72) {
            $errors[] = 'Password must be between 8 and 72 bytes.';
        }
        if (!hash_equals($password, $passwordConfirmation)) {
            $errors[] = 'Password confirmation does not match.';
        }
        if (strlen($preferredLocation) > 180) {
            $errors[] = 'Preferred location must be 180 characters or fewer.';
        }

        if ($errors !== []) {
            set_flash('errors', $errors);
            redirect_to_page('register');
        }

        try {
            $db = database_connection();
            $userModel = new User($db);
            $programModel = new Program($db);

            if ($userModel->emailExists($email)) {
                set_flash('errors', ['An account already uses this email address.']);
                redirect_to_page('register');
            }
            if (!$programModel->exists((int) $programId)) {
                set_flash('errors', ['The selected academic program is invalid.']);
                redirect_to_page('register');
            }

            if ($userModel->studentNumberExists($studentNumber)) {
                set_flash('errors', ['This student number is already registered.']);
                redirect_to_page('register');
            }

            $userModel->createStudent([
                'name' => $name,
                'email' => $email,
                'student_number' => $studentNumber,
                'program_id' => (int) $programId,
                'year_level' => (int) $yearLevel,
                'preferred_location' => $preferredLocation,
                'password' => $password,
            ]);

            destroy_secure_session();
            start_secure_session();
            set_flash('success', 'Registration completed. You can now sign in.');
            redirect_to_page('login');
        } catch (PDOException $exception) {
            if (($exception->errorInfo[1] ?? null) === 1062) {
                set_flash('errors', ['An account already uses this email address or student number.']);
                redirect_to_page('register');
            }
            error_log('Student registration failed due to a database error.');
            set_flash('error', 'Registration could not be completed. Please try again.');
            redirect_to_page('register');
        } catch (Throwable) {
            error_log('Student registration failed unexpectedly.');
            set_flash('error', 'Registration is temporarily unavailable.');
            redirect_to_page('register');
        }
    }

    public function login(): void
    {
        start_secure_session();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect_to_page('login');
        }

        if (!verify_csrf_post()) {
            set_flash('error', 'Your form session expired. Please try again.');
            redirect_to_page('login');
        }

        $email = strtolower(trim($this->postString('email')));
        $password = $this->postString('password');
        $validMessage = 'The email or password is incorrect.';

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
            set_flash('error', $validMessage);
            redirect_to_page('login');
        }

        try {
            $user = (new User(database_connection()))->authenticate($email, $password);
            if ($user === null) {
                set_flash('error', $validMessage);
                redirect_to_page('login');
            }

            establish_authenticated_session($user);
            redirect_to_dashboard((string) $user['role']);
        } catch (Throwable) {
            error_log('Sign-in failed unexpectedly.');
            set_flash('error', 'Sign-in is temporarily unavailable.');
            redirect_to_page('login');
        }
    }

    public function logout(): void
    {
        start_secure_session();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect_to_page('home');
        }
        if (!verify_csrf_post()) {
            redirect_to_page('home');
        }

        destroy_secure_session();
        redirect_to_page('login');
    }

    private function postString(string $key): string
    {
        $value = $_POST[$key] ?? '';

        return is_string($value) ? $value : '';
    }

    private function postInteger(string $key): int|false
    {
        $value = $_POST[$key] ?? null;
        if (!is_string($value) && !is_int($value)) {
            return false;
        }

        $validated = filter_var($value, FILTER_VALIDATE_INT);

        return $validated === false ? false : $validated;
    }
}
