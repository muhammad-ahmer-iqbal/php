<?php
// Load this file before HTML so sessions, cookies, and redirects can work.
// PHP stores session data on the server. The browser keeps a session ID cookie.
session_start();

// Our student array replaces the database. It lasts only for this session.
if (!isset($_SESSION['students'])) {
    $_SESSION['students'] = [];
    $_SESSION['next_id'] = 1;
}

// Each form sends this token to help prevent submissions from other websites.
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Escape user-entered text before displaying it inside HTML.
function escape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// Compare the form's hidden token with the one saved in the session.
function validFormToken()
{
    $token = $_POST['csrf_token'] ?? '';
    return is_string($token) && hash_equals($_SESSION['csrf_token'], $token);
}

// Create and edit share these rules. An empty return value means no errors.
function validateStudent($student)
{
    // This pattern accepts between 1 and 100 Unicode characters.
    if (!preg_match('/^.{1,100}$/us', $student['name']) || !preg_match('/^.{1,100}$/us', $student['course'])) {
        return 'Name and course are required and must be at most 100 characters.';
    }

    if (!filter_var($student['email'], FILTER_VALIDATE_EMAIL) || strlen($student['email']) > 150) {
        return 'Enter a valid email address of up to 150 characters.';
    }

    return '';
}

// Cookies store preferences in the browser; student records stay in the session.
// Remember the last course so the next Add student form can prefill it.
function rememberCourse($course)
{
    setcookie('crud_last_course', $course, [
        'expires' => time() + (30 * 24 * 60 * 60), // 30 days, in seconds.
        'path' => rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') . '/', // This app's folder.
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true, // PHP can read this cookie; JavaScript cannot.
        'samesite' => 'Lax',
    ]);
    // The browser sends the new value in $_COOKIE on the next request.
}
